<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class DriverBoat extends Model
{
    use BelongsToTenant;
    //
}
