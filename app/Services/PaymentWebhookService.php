<?php

namespace App\Services;

use App\Models\operations;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentWebhookService
{
    public function __construct(
        private readonly BookingLifecycleService $lifecycle,
        private readonly BookingNotificationDispatcher $notifications,
    ) {}

    public function handle(Request $request, string $provider): array
    {
        $secret = config("services.marinax_payments.webhook_secrets.{$provider}");
        if (! is_string($secret) || $secret === '') {
            throw new HttpException(503, 'Payment webhook verification is not configured.');
        }

        $signature = $request->header('X-Payment-Signature');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if (! is_string($signature) || ! hash_equals($expected, $signature)) {
            throw new HttpException(401, 'Invalid payment webhook signature.');
        }

        $data = Validator::make($request->json()->all(), [
            'event_id' => ['required', 'string', 'max:191'],
            'type' => ['required', 'string', 'in:payment.succeeded,payment.failed,payment.refunded'],
            'transaction_reference' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ])->validate();
        $rawPayloadHash = hash('sha256', $request->getContent());

        try {
            $result = DB::transaction(function () use ($provider, $data, $rawPayloadHash): array {
                $existingEvent = DB::table('payment_webhook_events')
                    ->where('provider', $provider)
                    ->where('event_id', $data['event_id'])
                    ->first();
                if ($existingEvent) {
                    if (! hash_equals($existingEvent->payload_hash, $rawPayloadHash)) {
                        throw new HttpException(409, 'Webhook event ID was already used with a different payload.');
                    }

                    return ['duplicate' => true, 'operation' => null, 'event' => $data['type']];
                }

                $payment = DB::table('payments')
                    ->where('provider', $provider)
                    ->where('transaction_reference', $data['transaction_reference'])
                    ->lockForUpdate()
                    ->first();
                if (! $payment) {
                    throw new HttpException(404, 'Payment transaction was not found.');
                }

                DB::table('payment_webhook_events')->insert([
                    'provider' => $provider,
                    'event_id' => $data['event_id'],
                    'payload_hash' => $rawPayloadHash,
                    'payment_id' => $payment->id,
                    'processed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                TenantContext::initialize((int) $payment->tenant_id);
                $operation = operations::query()->whereKey($payment->operation_id)->lockForUpdate()->firstOrFail();
                $notifyEvents = [];
                $amount = (float) $data['amount'];

                if ($data['type'] === 'payment.succeeded') {
                    if (! in_array($operation->booking_status, ['pending', 'confirmed'], true)) {
                        throw new HttpException(409, 'A cancelled or refunded booking cannot receive a successful payment.');
                    }
                    if (abs($amount - (float) $payment->amount) > 0.001) {
                        throw ValidationException::withMessages(['amount' => 'Webhook amount does not match the recorded payment.']);
                    }
                    if (! in_array($payment->status, ['pending', 'succeeded'], true)) {
                        throw new HttpException(409, 'Payment is not in a state that can be marked successful.');
                    }
                    if ($payment->status === 'pending') {
                        DB::table('payments')->where('id', $payment->id)->update([
                            'status' => 'succeeded',
                            'succeeded_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $notifyEvents[] = 'payment_succeeded';
                    }
                } elseif ($data['type'] === 'payment.failed') {
                    if (abs($amount - (float) $payment->amount) > 0.001) {
                        throw ValidationException::withMessages(['amount' => 'Webhook amount does not match the recorded payment.']);
                    }
                    if ($payment->status === 'pending') {
                        DB::table('payments')->where('id', $payment->id)->update([
                            'status' => 'failed',
                            'updated_at' => now(),
                        ]);
                        $notifyEvents[] = 'payment_failed';
                    }
                } else {
                    $matchedRefund = DB::table('payment_refunds')
                        ->where('payment_id', $payment->id)
                        ->where('status', 'pending')
                        ->where('amount', $amount)
                        ->orderBy('id')
                        ->first();
                    $pendingRefunds = DB::table('payment_refunds')
                        ->where('payment_id', $payment->id)
                        ->where('status', 'pending')
                        ->when($matchedRefund, fn ($query) => $query->where('id', '!=', $matchedRefund->id))
                        ->sum('amount');
                    $remaining = (float) $payment->amount - (float) $payment->amount_refunded - (float) $pendingRefunds;
                    if (! in_array($payment->status, ['succeeded', 'partially_refunded'], true) || $amount > $remaining) {
                        throw ValidationException::withMessages(['amount' => 'Refund amount exceeds the refundable payment balance.']);
                    }
                    $newRefundedAmount = (float) $payment->amount_refunded + $amount;
                    DB::table('payments')->where('id', $payment->id)->update([
                        'amount_refunded' => $newRefundedAmount,
                        'status' => $newRefundedAmount >= (float) $payment->amount ? 'refunded' : 'partially_refunded',
                        'refunded_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($matchedRefund) {
                        DB::table('payment_refunds')->where('id', $matchedRefund->id)->update([
                            'status' => 'succeeded',
                            'transaction_reference' => $data['transaction_reference'],
                            'processed_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('payment_refunds')->insert([
                            'tenant_id' => $payment->tenant_id,
                            'payment_id' => $payment->id,
                            'idempotency_key_hash' => hash_hmac(
                                'sha256',
                                'webhook-refund:'.$provider.':'.$data['event_id'],
                                (string) config('app.key'),
                            ),
                            'amount' => $amount,
                            'status' => 'succeeded',
                            'transaction_reference' => $data['transaction_reference'],
                            'processed_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $notifyEvents[] = 'payment_refunded';
                }

                $balances = DB::table('payments')
                    ->where('tenant_id', $payment->tenant_id)
                    ->where('operation_id', $operation->id)
                    ->whereIn('status', ['succeeded', 'partially_refunded', 'refunded'])
                    ->selectRaw('COALESCE(SUM(amount - amount_refunded), 0) AS paid')
                    ->first();
                $paid = min((float) $operation->amount_total, (float) $balances->paid);
                if ((float) $balances->paid > (float) $operation->amount_total + 0.001) {
                    throw ValidationException::withMessages(['amount' => 'Recorded provider payments exceed the booking total.']);
                }
                $paymentStatus = $paid >= (float) $operation->amount_total && $paid > 0
                    ? 'paid'
                    : ($paid > 0 ? 'partial' : ($data['type'] === 'payment.refunded' ? 'refunded' : 'pending'));
                $operation->forceFill([
                    'amount_paid' => $paid,
                    'amount_remaining' => max(0, (float) $operation->amount_total - $paid),
                    'status_payment' => $paymentStatus,
                ])->save();

                if ($data['type'] === 'payment.succeeded' && $paid > 0 && $paid >= (float) $operation->amount_total) {
                    if ($operation->booking_status === 'pending') {
                        $operation = $this->lifecycle->transition($operation->id, $operation->tenant_id, 'confirmed', null, 'Payment provider confirmed the booking.', 'payment_provider', $provider);
                        $notifyEvents[] = 'confirmed';
                    }
                    if ($operation->booking_status === 'confirmed') {
                        $operation = $this->lifecycle->transition($operation->id, $operation->tenant_id, 'paid', null, 'Payment provider confirmed payment.', 'payment_provider', $provider);
                        $notifyEvents[] = 'paid';
                    }
                } elseif ($data['type'] === 'payment.refunded' && $operation->booking_status === 'paid' && $this->isFullyRefunded($operation->id, (int) $operation->tenant_id)) {
                    $operation = $this->lifecycle->transition($operation->id, $operation->tenant_id, 'refunded', null, 'Payment provider confirmed refund.', 'payment_provider', $provider);
                    $notifyEvents[] = 'refunded';
                }

                return ['duplicate' => false, 'operation' => $operation, 'events' => $notifyEvents];
            });
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw $exception;
            }

            $existingEvent = DB::table('payment_webhook_events')
                ->where('provider', $provider)
                ->where('event_id', $data['event_id'])
                ->first();
            if (! $existingEvent) {
                throw $exception;
            }
            if (! hash_equals($existingEvent->payload_hash, $rawPayloadHash)) {
                throw new HttpException(409, 'Webhook event ID was already used with a different payload.');
            }

            return ['duplicate' => true];
        } finally {
            TenantContext::clear();
        }

        if ($result['operation'] && $result['events']) {
            foreach ($result['events'] as $event) {
                $this->notifications->dispatch(
                    $result['operation'],
                    $event,
                    'Payment event processed: '.$event.'.',
                );
            }
        }

        return ['duplicate' => $result['duplicate']];
    }

    private function isFullyRefunded(int $operationId, int $tenantId): bool
    {
        $totals = DB::table('payments')
            ->where('operation_id', $operationId)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['succeeded', 'partially_refunded', 'refunded'])
            ->selectRaw('COALESCE(SUM(amount), 0) AS paid, COALESCE(SUM(amount_refunded), 0) AS refunded')
            ->first();

        return $totals && (float) $totals->paid > 0 && (float) $totals->refunded >= (float) $totals->paid;
    }
}
