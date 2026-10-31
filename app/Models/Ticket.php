<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Ticket extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'operation_id',
        'ticket_code',
        'token',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function operation()
    {
        return $this->belongsTo(operations::class, 'operation_id');
    }
}
