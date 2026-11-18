<?php

namespace App\Contracts;

interface WhatsAppTransport
{
    public function send(string $recipient, string $message): void;
}
