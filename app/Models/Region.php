<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToTenant;

class Region extends Model
{
    use BelongsToTenant;

    protected $table = 'regions';

    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'staff_region_assignments')
            ->withTimestamps();
    }

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class, 'region_id');
    }

    public function boats(): HasMany
    {
        return $this->hasMany(Boat::class, 'region_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'region_id');
    }
}
