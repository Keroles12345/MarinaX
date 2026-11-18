<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Prices_company extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'company_id',
        'trip_id',
        'currency_id',
        'adult_price',
        'child_price',
        'infant_price',
    ];

    public function company()
    {
        return $this->belongsTo(Companies::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trips::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currencies::class);
    }
}
