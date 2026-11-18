<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $eventType,
        private readonly string $bookingId,
        private readonly string $details,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('MarinaX booking update: '.$this->eventType)
            ->greeting('Booking update')
            ->line('Booking '.$this->bookingId.': '.$this->details);
    }
}
