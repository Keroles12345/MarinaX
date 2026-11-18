<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Extras extends Model
{
    use BelongsToTenant;
    protected $table = 'extras';

    protected $fillable = [
        'name',
        'price',
        'rate_tax',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rate_tax' => 'decimal:2',
        'status' => 'boolean',
    ];
}
