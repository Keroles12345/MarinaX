<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoatDriver extends Model
{
    use BelongsToTenant;
    protected $table = 'boat_drivers';

    protected $fillable = [
        'boat_id',
        'driver_id',
        'assignment_role',
    ];

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class, 'boat_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Boat_Driver::class, 'driver_id');
    }
}
