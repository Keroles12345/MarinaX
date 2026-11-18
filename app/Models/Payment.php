<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'operation_id',
        'provider',
        'amount',
        'amount_refunded',
        'currency_code',
        'status',
        'transaction_reference',
        'idempotency_key_hash',
        'metadata',
        'succeeded_at',
        'refunded_at',
    ];

    protected $hidden = ['idempotency_key_hash', 'metadata'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_refunded' => 'decimal:2',
            'metadata' => 'array',
            'succeeded_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(operations::class, 'operation_id');
    }
}
