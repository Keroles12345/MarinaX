<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class customers extends Model
{
    use BelongsToTenant;

protected $tableName='customers';
      protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'notes',
    ];

}
