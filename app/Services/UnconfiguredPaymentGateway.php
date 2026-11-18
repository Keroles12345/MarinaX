<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Exceptions\PaymentProviderNotConfigured;

class UnconfiguredPaymentGateway implements PaymentGateway
{
    public function createIntent(string $idempotencyKey, int $amountMinor, string $currency, array $metadata): array
    {
        throw new PaymentProviderNotConfigured;
    }

    public function refund(string $transactionReference, string $idempotencyKey, int $amountMinor): void
    {
        throw new PaymentProviderNotConfigured;
    }
}
