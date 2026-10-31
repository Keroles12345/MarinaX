<?php

namespace App\Support;

final class TenantContext
{
    private static bool $initialized = false;

    private static ?int $tenantId = null;

    public static function initialize(?int $tenantId): void
    {
        self::$initialized = true;
        self::$tenantId = $tenantId;
    }

    public static function clear(): void
    {
        self::$initialized = false;
        self::$tenantId = null;
    }

    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    public static function id(): ?int
    {
        return self::$tenantId;
    }
}
