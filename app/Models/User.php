<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Concerns\BelongsToTenant;
#[Fillable([
    'name',
    'email',
    'password',
    'username',
    'password_hash',
    'security_question',
    'security_answer_hash',
    'role',
    'status',
    'tenant_id',
    'is_super_admin',
    'system_permissions_managed',
])]
#[Hidden(['password', 'password_hash', 'security_answer_hash', 'remember_token'])]
class User extends Authenticatable
{
    protected string $guard_name = 'web';

    /** @use HasFactory<UserFactory> */
use HasFactory, Notifiable, HasRoles, BelongsToTenant;

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }
    public function staffProfile(): HasOne
    {
        return $this->hasOne(staff_profiles::class, 'user_id');
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'staff_region_assignments')
            ->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'tenant_id' => 'integer',
            'is_super_admin' => 'boolean',
            'system_permissions_managed' => 'boolean',
        ];
    }
}
