<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TenantDatabase
{
    private const TENANT_TABLES = [
        'users',
        'customer_profiles',
        'departments',
        'staff_profiles',
        'companies',
        'customers',
        'suppliers',
        'trips',
        'hotels',
        'trips_hotels',
        'operations',
        'extras',
        'operation_extras',
        'prices_company',
        'tickets',
        'boats',
        'vehicles',
        'drives',
        'vehicle_drivers',
        'driver_types',
        'boat_drivers',
        'operation_assignments',
        'flights',
        'driver_boats',
        'accounts',
        'regions',
        'journal_entries',
        'journal_lines',
        'settings',
        'audit_log',
        'employee_custody_deposits',
        'staff_region_assignments',
    ];

    public static function table(string $table): Builder
    {
        $connection = DB::connection();
        [$tableName, $qualifier] = self::tableAndQualifier($table);

        if (! in_array($tableName, self::TENANT_TABLES, true) || ! TenantContext::isInitialized()) {
            return $connection->table($table);
        }

        $tenantId = TenantContext::id();
        if ($tenantId === null) {
            return $connection->table($table)->whereRaw('1 = 0');
        }

        $query = new TenantQueryBuilder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor(),
            $tenantId,
        );
        $query->from($table)->where($qualifier.'.tenant_id', $tenantId);

        return $query;
    }

    private static function tableAndQualifier(string $table): array
    {
        $parts = preg_split('/\s+as\s+|\s+/i', trim($table));
        $tableName = trim($parts[0], '`');
        $qualifier = trim($parts[1] ?? $tableName, '`');

        return [$tableName, $qualifier];
    }
}
