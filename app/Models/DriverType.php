<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverType extends Model
{
    use BelongsToTenant;
    protected $table = 'driver_types';

    protected $fillable = [
        'name',
    ];

    public function drives(): HasMany
    {
        return $this->hasMany(Drive::class, 'driver_type_id');
    }
}
