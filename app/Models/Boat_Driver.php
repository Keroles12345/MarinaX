<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Boat_Driver extends Model
{
    use BelongsToTenant;
protected $table = 'driver_boats';
protected $fillable = [ 'name', 'phone', 'boat_id', 'license_number', 'is_active', ];
 protected $casts = [ 'is_active' => 'boolean', ];
 public function boat() { return $this->belongsTo(Boat::class); }
}
