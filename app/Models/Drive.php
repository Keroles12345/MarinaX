<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drive extends Model
{
    use BelongsToTenant;
    protected $table = 'drives';

    protected $fillable = [
        'name',
        'phone',
        'license_number',
        'status',
        'account_id',
        'LicenseDate',
        'degreeOfLeave',
        'email',
        'driver_type_id',
    ];

    protected $casts = [
        'LicenseDate' => 'date',
    ];

    public function driverType(): BelongsTo
    {
        return $this->belongsTo(DriverType::class);
    }

    public function vehicleDrivers(): HasMany
    {
        return $this->hasMany(VehicleDriver::class, 'driver_id');
    }

    public function boatDrivers(): HasMany
    {
        return $this->hasMany(BoatDriver::class, 'driver_id');
    }
}
