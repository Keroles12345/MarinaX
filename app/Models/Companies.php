<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Companies extends Model
{
    use BelongsToTenant;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
