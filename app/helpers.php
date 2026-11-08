<?php

use App\Services\CurrencyService;

if (! function_exists('currency')) {
    function currency(): CurrencyService
    {
        return app(CurrencyService::class);
    }
}
