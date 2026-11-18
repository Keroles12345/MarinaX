<?php

namespace App\Support;

final class TenantApiScopes
{
    public const DEFAULT = [
        'trips:read',
        'companies:read',
        'currencies:read',
        'bookings:create',
        'bookings:read',
    ];

    public const AVAILABLE = [
        'trips:read',
        'companies:read',
        'currencies:read',
        'bookings:create',
        'bookings:read',
        'bookings:manage',
        'payments:create',
        'payments:refund',
    ];
}
