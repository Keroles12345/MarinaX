<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currencies  extends Model
{
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'exchange_rate',
        'is_default',
        'is_active',
    ];
}
