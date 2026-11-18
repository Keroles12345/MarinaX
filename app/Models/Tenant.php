<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'address', 'status'];

    protected $hidden = ['api_key_hash'];

    protected $casts = [
        'status' => 'boolean',
        'api_key_expires_at' => 'date',
        'api_key_last_used_at' => 'datetime',
        'api_key_scopes' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
