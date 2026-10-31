<?php

namespace App\Services;

use App\Models\Currencies;

class CurrencyService
{
    protected ?Currencies $selectedCurrency = null;

    // Get the default currency
  public function default(){
    return Currencies::where('is_default',true)
            ->where('is_active', true)
    ->firstOrFail();
  }
    // Set the selected currency
    public function set(int $currencyId): void
{
    $this->selectedCurrency=Currencies::where('id',$currencyId)
      ->where('is_active', true)    
    ->firstOrFail();
    session([
'currency_id'=>$currencyId
    ]);
}

    // Get the selected currency
    public function selected(): Currencies
    {
        // If currency is already loaded
        if ($this->selectedCurrency) {
            return $this->selectedCurrency;
        }

        // Get currency from session
        $currencyId = session('currency_id');

        if ($currencyId) {
            $this->selectedCurrency = Currencies::where('id', $currencyId)
                ->where('is_active', true)
                ->first();
        }

        // If no currency selected, use default
        if (!$this->selectedCurrency) {
            $this->selectedCurrency = $this->default();
        }

        return $this->selectedCurrency;
    }

    // Convert from default currency to selected currency
    public function convert(float $amount): float
    {
        return $amount / (float) $this->selected()->exchange_rate;
    }

    // Get selected currency exchange rate
    public function rate(): float
    {
        return (float) $this->selected()->exchange_rate;
    }

    // Get selected currency code
    public function code(): string
    {
        return $this->selected()->code;
    }

    // Get selected currency symbol
    public function symbol(): ?string
    {
        return $this->selected()->symbol;
    }
}
