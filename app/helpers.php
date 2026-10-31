<?php

use App\Services\CurrencyService;

function currency(): CurrencyService
{
    return app(CurrencyService::class);
}
