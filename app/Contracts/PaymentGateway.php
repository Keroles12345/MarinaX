<?php

namespace App\Contracts;

interface PaymentGateway
{
    /**
     * @return array{transaction_reference: string, redirect_url: string|null}
     */
    public function createIntent(string $idempotencyKey, int $amountMinor, string $currency, array $metadata): array;

    public function refund(string $transactionReference, string $idempotencyKey, int $amountMinor): void;
}
