<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class staff_profiles extends Model
{
    use BelongsToTenant;
    public function department()
{
    return $this->belongsTo(departments::class);
}
public function user() { return $this->belongsTo(users::class); }
}
