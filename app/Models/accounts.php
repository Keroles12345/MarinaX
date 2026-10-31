<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class accounts extends Model
{
   use BelongsToTenant;

   protected $table = 'accounts';

    protected $fillable = [
        'acc_no',
        'name',
        'type',
        'opening_balance',
        'budget',
        'is_system',

    ];

    protected $casts = ['is_system' => 'boolean'];

}
