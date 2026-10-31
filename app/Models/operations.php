<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class operations extends Model
{
    use BelongsToTenant;

    protected $table = 'operations';

    protected $fillable = [
        'booking_id',
        'id_trip',
        'id_company',
        'supplier_id',
        'customer_id',
        'trip_hotel_id',
        'id_currency',
        'trip_date',
        'trip_time',
        'time_pickup',
        'number_room',
        'adults',
        'children',
        'infants',
        'price_adult',
        'price_child',
        'price_infant',
        'subtotal',
        'amount_extra',
        'amount_discount',
        'rate_tax',
        'amount_tax',
        'amount_total',
        'totalamount',
        'method_payment',
        'status_payment',
        'amount_paid',
        'amount_remaining',
        'remarks',
        'status',
        'by_created',
    ];

    protected $casts = [
        'trip_date'    => 'date',
        'trip_time'    => 'string',
        'time_pickup'  => 'string',

        'adults'       => 'integer',
        'children'     => 'integer',
        'infants'      => 'integer',

        'price_adult'  => 'decimal:2',
        'price_child'  => 'decimal:2',
        'price_infant' => 'decimal:2',

        'subtotal'        => 'decimal:2',
        'amount_extra'    => 'decimal:2',
        'amount_discount' => 'decimal:2',
        'rate_tax'        => 'decimal:2',
        'amount_tax'      => 'decimal:2',
        'amount_total'    => 'decimal:2',
        'totalamount'     => 'decimal:2',
        'amount_paid'    => 'decimal:2',
        'amount_remaining'=> 'decimal:2',
    ];
    public function ticket()
{
    return $this->hasOne(Ticket::class, 'operation_id');
}

    public function tripHotel()
    {
        return $this->belongsTo(Trips_hotels::class, 'trip_hotel_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trips::class, 'id_trip');
    }
}
