<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentProviderNotConfigured extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No payment provider is configured for this installation.');
    }
}
