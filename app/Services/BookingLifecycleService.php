<?php

namespace App\Services;

use App\Models\operations;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingLifecycleService
{
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['paid', 'cancelled'],
        'paid' => ['refunded'],
        'cancelled' => [],
        'refunded' => [],
    ];

    public function transition(
        int $bookingId,
        int $tenantId,
        string $nextStatus,
        ?int $userId,
        ?string $reason = null,
        string $actorType = 'api_key',
        ?string $actorIdentifier = null,
    ): operations {
        return DB::transaction(function () use ($bookingId, $tenantId, $nextStatus, $userId, $reason, $actorType, $actorIdentifier): operations {
            $operation = operations::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->first();

            if (! $operation) {
                throw (new ModelNotFoundException)->setModel(operations::class, [$bookingId]);
            }

            $currentStatus = $operation->booking_status;
            if (! in_array($nextStatus, self::TRANSITIONS[$currentStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Booking status cannot transition from {$currentStatus} to {$nextStatus}.",
                ]);
            }

            if ($nextStatus === 'paid' && ! $this->isFullyPaid($operation)) {
                throw ValidationException::withMessages([
                    'status' => 'A booking cannot be marked paid until its full amount is recorded as paid.',
                ]);
            }

            if ($nextStatus === 'refunded' && ! $this->isFullyRefunded($operation)) {
                throw ValidationException::withMessages([
                    'status' => 'A booking cannot be marked refunded until all recorded payments are refunded.',
                ]);
            }

            $operation->forceFill(['booking_status' => $nextStatus])->save();
            DB::table('operation_status_history')->insert([
                'tenant_id' => $tenantId,
                'operation_id' => $operation->id,
                'from_status' => $currentStatus,
                'to_status' => $nextStatus,
                'changed_by' => $userId,
                'actor_type' => $userId ? 'user' : $actorType,
                'actor_identifier' => $userId ? (string) $userId : $actorIdentifier,
                'reason' => $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $operation;
        });
    }

    private function isFullyPaid(operations $operation): bool
    {
        if ((float) $operation->amount_total <= 0) {
            return false;
        }

        if ($operation->status_payment === 'paid' && (float) $operation->amount_paid >= (float) $operation->amount_total) {
            return true;
        }

        $paidAmount = (float) DB::table('payments')
            ->where('tenant_id', $operation->tenant_id)
            ->where('operation_id', $operation->id)
            ->whereIn('status', ['succeeded', 'partially_refunded'])
            ->sum(DB::raw('amount - amount_refunded'));

        return $paidAmount >= (float) $operation->amount_total;
    }

    private function isFullyRefunded(operations $operation): bool
    {
        $totals = DB::table('payments')
            ->where('tenant_id', $operation->tenant_id)
            ->where('operation_id', $operation->id)
            ->whereIn('status', ['succeeded', 'partially_refunded', 'refunded'])
            ->selectRaw('COALESCE(SUM(amount), 0) AS paid, COALESCE(SUM(amount_refunded), 0) AS refunded')
            ->first();

        return $totals && (float) $totals->paid > 0 && (float) $totals->refunded >= (float) $totals->paid;
    }
}
