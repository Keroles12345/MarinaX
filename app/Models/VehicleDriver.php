<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDriver extends Model
{
    use BelongsToTenant;
    protected $table = 'vehicle_drivers';

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'assignment_role',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Drive::class, 'driver_id');
    }
}
