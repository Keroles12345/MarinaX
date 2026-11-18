<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Exceptions\PaymentProviderNotConfigured;
use App\Models\Currencies;
use App\Models\operations;
use App\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function createIntent(string $bookingId, float $amount, string $idempotencyKey): array
    {
        $provider = config('services.marinax_payments.provider');
        if (! is_string($provider) || $provider === '') {
            throw new PaymentProviderNotConfigured;
        }

        $tenantId = (int) TenantContext::id();
        $keyHash = hash_hmac('sha256', $idempotencyKey, (string) config('app.key'));
        $operation = operations::query()->where('booking_id', $bookingId)->firstOrFail();
        $currency = Currencies::query()->where('is_active', true)->findOrFail($operation->id_currency);

        try {
            $payment = DB::transaction(function () use ($operation, $provider, $amount, $keyHash, $currency, $tenantId): Payment {
                $lockedOperation = operations::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($operation->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $existing = Payment::query()
                    ->where('tenant_id', $tenantId)
                    ->where('idempotency_key_hash', $keyHash)
                    ->first();
                if ($existing) {
                    $this->assertSameIntent($existing, $lockedOperation, $amount);

                    return $existing;
                }

                if (! in_array($lockedOperation->booking_status, ['pending', 'confirmed'], true)) {
                    throw ValidationException::withMessages(['booking' => 'Only pending or confirmed bookings can accept a new payment.']);
                }

                $pendingAmount = (float) Payment::query()
                    ->where('tenant_id', $tenantId)
                    ->where('operation_id', $lockedOperation->id)
                    ->where('status', 'pending')
                    ->sum('amount');
                if ($amount <= 0 || $amount > (float) $lockedOperation->amount_remaining - $pendingAmount) {
                    throw ValidationException::withMessages(['amount' => 'Payment amount must be positive and no greater than the unreserved booking balance.']);
                }

                return Payment::query()->create([
                    'operation_id' => $lockedOperation->id,
                    'provider' => $provider,
                    'amount' => $amount,
                    'currency_code' => $currency->code,
                    'status' => 'pending',
                    'idempotency_key_hash' => $keyHash,
                ]);
            });
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw $exception;
            }
            $payment = Payment::query()
                ->where('tenant_id', $tenantId)
                ->where('idempotency_key_hash', $keyHash)
                ->firstOrFail();
            $this->assertSameIntent($payment, $operation, $amount);
        }

        if ($payment->transaction_reference && isset($payment->metadata['redirect_url'])) {
            return ['payment' => $payment, 'redirect_url' => $payment->metadata['redirect_url']];
        }

        try {
            $intent = $this->gateway->createIntent(
                $idempotencyKey,
                (int) round($amount * 100),
                $payment->currency_code,
                ['booking_id' => $operation->booking_id, 'tenant_id' => $tenantId],
            );
        } catch (PaymentProviderNotConfigured $exception) {
            $payment->delete();
            throw $exception;
        }

        $payment->forceFill([
            'transaction_reference' => $intent['transaction_reference'],
            'metadata' => ['redirect_url' => $intent['redirect_url']],
        ])->save();

        return ['payment' => $payment, 'redirect_url' => $intent['redirect_url']];
    }

    public function refund(int $paymentId, float $amount, string $idempotencyKey): Payment
    {
        $tenantId = (int) TenantContext::id();
        $provider = config('services.marinax_payments.provider');
        if (! is_string($provider) || $provider === '') {
            throw new PaymentProviderNotConfigured;
        }
        $keyHash = hash_hmac('sha256', $idempotencyKey, (string) config('app.key'));
        try {
            $refund = DB::transaction(function () use ($paymentId, $tenantId, $keyHash, $amount): object {
                $lockedPayment = Payment::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($paymentId)
                    ->lockForUpdate()
                    ->first();
                if (! $lockedPayment) {
                    throw (new ModelNotFoundException)->setModel(Payment::class, [$paymentId]);
                }

                $existing = DB::table('payment_refunds')
                    ->where('tenant_id', $tenantId)
                    ->where('idempotency_key_hash', $keyHash)
                    ->first();
                if ($existing) {
                    if ((int) $existing->payment_id !== (int) $lockedPayment->id || abs((float) $existing->amount - $amount) > 0.001) {
                        throw ValidationException::withMessages(['Idempotency-Key' => 'This key was already used for a different refund request.']);
                    }

                    return $existing;
                }

                if (! in_array($lockedPayment->status, ['succeeded', 'partially_refunded'], true)) {
                    throw ValidationException::withMessages(['payment' => 'Only successful payments can be refunded.']);
                }

                $pendingRefunds = (float) DB::table('payment_refunds')
                    ->where('tenant_id', $tenantId)
                    ->where('payment_id', $lockedPayment->id)
                    ->where('status', 'pending')
                    ->sum('amount');
                $remaining = (float) $lockedPayment->amount - (float) $lockedPayment->amount_refunded - $pendingRefunds;
                if ($amount <= 0 || $amount > $remaining) {
                    throw ValidationException::withMessages(['amount' => 'Refund amount must be positive and no greater than the refundable balance.']);
                }

                $refundId = DB::table('payment_refunds')->insertGetId([
                    'tenant_id' => $tenantId,
                    'payment_id' => $lockedPayment->id,
                    'idempotency_key_hash' => $keyHash,
                    'amount' => $amount,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return DB::table('payment_refunds')->where('id', $refundId)->first();
            });
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw $exception;
            }
            $refund = DB::table('payment_refunds')
                ->where('tenant_id', $tenantId)
                ->where('idempotency_key_hash', $keyHash)
                ->first();
            if (! $refund || (int) $refund->payment_id !== $paymentId || abs((float) $refund->amount - $amount) > 0.001) {
                throw ValidationException::withMessages(['Idempotency-Key' => 'This key was already used for a different refund request.']);
            }
        }

        if ($refund->status === 'pending') {
            $lockedPayment = Payment::query()->where('tenant_id', $tenantId)->findOrFail($paymentId);
            try {
                $this->gateway->refund((string) $lockedPayment->transaction_reference, $idempotencyKey, (int) round($amount * 100));
            } catch (PaymentProviderNotConfigured $exception) {
                DB::table('payment_refunds')->where('id', $refund->id)->delete();
                throw $exception;
            }
        }

        return Payment::query()->where('tenant_id', $tenantId)->findOrFail($paymentId);
    }

    private function assertSameIntent(Payment $payment, operations $operation, float $amount): void
    {
        if ((int) $payment->operation_id !== (int) $operation->id || abs((float) $payment->amount - $amount) > 0.001) {
            throw ValidationException::withMessages(['Idempotency-Key' => 'This key was already used for a different payment request.']);
        }
    }
}
