<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationAssignment extends Model
{
    use BelongsToTenant;
    protected $fillable = ['operation_id', 'type', 'resource_id', 'driver_id', 'assign_date'];

    protected $casts = ['assign_date' => 'date'];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(operations::class, 'operation_id');
    }
}
