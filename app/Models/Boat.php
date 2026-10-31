<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Boat extends Model
{
    use BelongsToTenant;

    protected $table = 'boats';

    protected $fillable = [
        'company_id',
        'region_id',
        'ownership_type',
        'supplier_id',
        'supplier_price',
        'name',
        'code',
        'capacity',
        'type',
        'registration_number',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'supplier_price' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Companies::class, 'company_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function boatDrivers(): HasMany
    {
        return $this->hasMany(Boat_Driver::class, 'boat_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(accounts::class, 'revenue_account_id');
    }
}
