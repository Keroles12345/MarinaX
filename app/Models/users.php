<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Spatie\Permission\Traits\HasRoles;
class users extends Model
{
    use BelongsToTenant;
    
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',

    ];
    public function staffProfile()
{
    return $this->hasOne(staff_profiles::class);
}
}
