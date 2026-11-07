<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use BelongsToTenant;

    protected $table = 'vehicles';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'region_id',
        'code',
        'status',
        'ownership_type',
        'supplier_id',
        'supplier_price',
        'revenue_account_id',
        'created_at',
        'codes',
        'color',
        'vehicle_type_id',
        'account_id',
        'codee',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'supplier_price' => 'decimal:2',
    ];

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function vehicleDrivers(): HasMany
    {
        return $this->hasMany(VehicleDriver::class, 'vehicle_id');
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(accounts::class, 'revenue_account_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
