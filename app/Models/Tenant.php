<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'address', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
