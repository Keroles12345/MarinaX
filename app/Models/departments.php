<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class departments extends Model
{
    use BelongsToTenant;
      protected $table = 'departments';
       protected $fillable = [
        'name',
        'description'


    ];
    public function staff()
{
    return $this->hasMany(staff_profiles::class);
}
}
