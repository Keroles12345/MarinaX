<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripImage extends Model
{
    use BelongsToTenant;

    protected $fillable = ['trip_id', 'path', 'sort_order'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trips::class, 'trip_id');
    }
}
