<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Trips_hotels extends Model
{
    use BelongsToTenant;
    protected $table = 'trips_hotels';

    protected $fillable = [
        'trip_id',
        'hotel_id',
        'pickup_time',
        'status',
    ];

    protected $casts = [
        'pickup_time' => 'string',
        'status' => 'boolean',
    ];

    public function trip()
    {
        return $this->belongsTo(Trips::class, 'trip_id');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}
