<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToTenant;

    protected $table = 'suppliers';

    protected $fillable = ['name', 'address', 'phone', 'email', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function boats(): HasMany
    {
        return $this->hasMany(Boat::class, 'supplier_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'supplier_id');
    }
}
