<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Hotel extends Model
{
    use BelongsToTenant;

    protected $table = 'hotels';

    protected $fillable = [
        'name',
        'region_id',
        'address',
        'phone',
        'email',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function tripHotels()
    {
        return $this->hasMany(Trips_hotels::class, 'hotel_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
