<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;

class Trips extends Model
{
    use BelongsToTenant;

    protected $table = 'trips';

    protected $fillable = [
        'name',
        'region_id',
        'time_trip',
        'description',
        'status',
        'includes',
        'excludes',
        'faqs',
    ];

    protected $casts = [
        'status' => 'boolean',
        'time_trip' => 'datetime:H:i',
        'includes' => 'array',
        'excludes' => 'array',
        'faqs' => 'array',
    ];

    public function tripHotels(): HasMany
    {
        return $this->hasMany(Trips_hotels::class, 'trip_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(TripImage::class, 'trip_id')->orderBy('sort_order');
    }
}
