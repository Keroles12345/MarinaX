<?php

namespace App\Services;

use App\Models\operations;
use App\Models\User;
use App\Notifications\BookingEventNotification;
use Illuminate\Support\Facades\DB;

class BookingNotificationDispatcher
{
    public function dispatch(operations $operation, string $eventType, string $details): void
    {
        $users = User::withoutGlobalScopes()
            ->where('tenant_id', $operation->tenant_id)
            ->where('status', true)
            ->where('is_super_admin', false)
            ->whereNotNull('email')
            ->get();

        foreach ($users as $user) {
            $inserted = DB::table('booking_notification_deliveries')->insertOrIgnore([
                'tenant_id' => $operation->tenant_id,
                'operation_id' => $operation->id,
                'event_type' => $eventType,
                'recipient' => $user->email,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 1) {
                $user->notify(new BookingEventNotification($eventType, $operation->booking_id, $details));
            }
        }
    }
}
