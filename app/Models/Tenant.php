<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'address', 'status'];

    protected $hidden = ['api_key_hash', 'api_key_encrypted'];

    protected $casts = [
        'status' => 'boolean',
        'api_key_expires_at' => 'date',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
