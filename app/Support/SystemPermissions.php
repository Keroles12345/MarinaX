<?php

namespace App\Support;

use App\Models\User;

final class SystemPermissions
{
    public const STAFF_MANAGED_MODULES = [
        'staff_dashboard',
        'operations',
        'daily_operations',
        'operation_assignments',
        'tickets',
    ];

    public const ACTIONS = [
        'view' => 'عرض',
        'create' => 'إضافة',
        'update' => 'تعديل',
        'delete' => 'حذف',
    ];

    public const MODULES = [
        'admin_dashboard' => ['label' => 'لوحة الإدارة', 'actions' => ['view']],
        'staff_dashboard' => ['label' => 'لوحة الموظفين', 'actions' => ['view']],
        'accounting_dashboard' => ['label' => 'لوحة الحسابات', 'actions' => ['view']],
        'supervisor_dashboard' => ['label' => 'لوحة المشرف', 'actions' => ['view']],
        'sales_dashboard' => ['label' => 'لوحة المبيعات', 'actions' => ['view']],
        'reseller_dashboard' => ['label' => 'لوحة الموزعين', 'actions' => ['view']],
        'operations' => ['label' => 'العمليات', 'actions' => ['view', 'create', 'update', 'delete']],
        'daily_operations' => ['label' => 'التشغيل اليومي', 'actions' => ['view', 'create', 'update', 'delete']],
        'operation_assignments' => ['label' => 'إسناد العمليات', 'actions' => ['view', 'create', 'update', 'delete']],
        'tickets' => ['label' => 'التذاكر', 'actions' => ['view', 'create', 'update', 'delete']],
        'accounts' => ['label' => 'دليل الحسابات', 'actions' => ['view', 'create', 'update', 'delete']],
        'journal' => ['label' => 'القيود اليومية', 'actions' => ['view', 'create', 'update', 'delete']],
        'reports' => ['label' => 'التقارير المالية', 'actions' => ['view', 'create', 'update', 'delete']],
        'regions' => ['label' => 'المناطق', 'actions' => ['view', 'create', 'update', 'delete']],
        'users' => ['label' => 'المستخدمون', 'actions' => ['view', 'create', 'update', 'delete']],
        'audit' => ['label' => 'سجل المراجعة', 'actions' => ['view']],
        'settings' => ['label' => 'الإعدادات والنسخ الاحتياطي', 'actions' => ['view', 'create', 'update', 'delete']],
        'employee_custody' => ['label' => 'عهد الموظفين', 'actions' => ['view', 'create', 'update', 'delete']],
        'boat_revenues' => ['label' => 'إيرادات المراكب', 'actions' => ['view', 'create', 'update', 'delete']],
        'vehicle_revenues' => ['label' => 'إيرادات المركبات', 'actions' => ['view', 'create', 'update', 'delete']],
        'trips' => ['label' => 'الرحلات', 'actions' => ['view', 'create', 'update', 'delete']],
        'hotels' => ['label' => 'الفنادق', 'actions' => ['view', 'create', 'update', 'delete']],
        'trip_hotels' => ['label' => 'ربط الرحلات بالفنادق', 'actions' => ['view', 'create', 'update', 'delete']],
        'suppliers' => ['label' => 'الموردون', 'actions' => ['view', 'create', 'update', 'delete']],
        'companies' => ['label' => 'الشركات والعملاء', 'actions' => ['view', 'create', 'update', 'delete']],
        'extras' => ['label' => 'الخدمات الإضافية', 'actions' => ['view', 'create', 'update', 'delete']],
        'company_prices' => ['label' => 'أسعار الشركات', 'actions' => ['view', 'create', 'update', 'delete']],
        'boats' => ['label' => 'المراكب', 'actions' => ['view', 'create', 'update', 'delete']],
        'vehicles' => ['label' => 'المركبات', 'actions' => ['view', 'create', 'update', 'delete']],
        'drivers' => ['label' => 'السائقون', 'actions' => ['view', 'create', 'update', 'delete']],
        'boat_drivers' => ['label' => 'قائدو المراكب', 'actions' => ['view', 'create', 'update', 'delete']],
    ];

    public static function names(): array
    {
        $names = [];
        foreach (self::MODULES as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $names[] = self::name($module, $action);
            }
        }

        return $names;
    }

    public static function staffNames(): array
    {
        return collect(self::STAFF_MANAGED_MODULES)
            ->flatMap(fn (string $module): array => array_map(
                fn (string $action): string => self::name($module, $action),
                self::MODULES[$module]['actions']
            ))
            ->all();
    }

    public static function staffDelegableNames(?User $admin): array
    {
        if (! $admin || $admin->role !== 'admin' || $admin->is_super_admin) {
            return [];
        }

        return $admin->permissions()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->intersect(self::staffNames())
            ->values()
            ->all();
    }

    public static function name(string $module, string $action): string
    {
        return "system.{$module}.{$action}";
    }

    public static function allows(?User $user, string $module, string $action = 'view'): bool
    {
        if (! $user || ! isset(self::MODULES[$module])
            || ! in_array($action, self::MODULES[$module]['actions'], true)) {
            return false;
        }

        if ($user->is_super_admin
            || ($user->role !== 'admin' && ! $user->system_permissions_managed)) {
            return true;
        }

        $permissionName = self::name($module, $action);
        $hasPermission = $user->permissions->contains(
            fn ($permission): bool => $permission->name === self::name($module, $action)
                && $permission->guard_name === 'web'
        );

        if (! $hasPermission) {
            return false;
        }

        if ($user->role !== 'staff' || ! $user->system_permissions_managed) {
            return true;
        }

        return $user->tenant_id !== null
            && User::withoutGlobalScopes()
                ->where('tenant_id', $user->tenant_id)
                ->where('role', 'admin')
                ->where('is_super_admin', false)
                ->whereHas('permissions', fn ($query) => $query
                    ->where('name', $permissionName)
                    ->where('guard_name', 'web'))
                ->exists();
    }

    public static function moduleForRoute(string $uri): ?string
    {
        $uri = trim($uri, '/');

        if ($uri === 'admin/dashboard') {
            return 'admin_dashboard';
        }
        if ($uri === 'staff/sales/dashboard') {
            return 'sales_dashboard';
        }
        if ($uri === 'staff/reseller/dashboard') {
            return 'reseller_dashboard';
        }
        if ($uri === 'supervisor/dashboard') {
            return 'supervisor_dashboard';
        }
        if ($uri === 'staff/operations') {
            return 'staff_dashboard';
        }
        if (str_starts_with($uri, 'staff/operations/') || str_starts_with($uri, 'staff/operation/')) {
            return 'operations';
        }
        if (str_starts_with($uri, 'daily-ops')) {
            return 'daily_operations';
        }
        if (str_starts_with($uri, 'operation-assignments')) {
            return 'operation_assignments';
        }
        if (str_starts_with($uri, 'ticket/')) {
            return 'tickets';
        }
        if (str_starts_with($uri, 'print/')) {
            return 'operations';
        }
        if (in_array($uri, ['accounting', 'home', 'api/home'], true)) {
            return 'accounting_dashboard';
        }
        if ($uri === 'admin/user-permissions' || str_starts_with($uri, 'api/me/')) {
            return 'users';
        }
        if (str_starts_with($uri, 'employee-custody')) {
            return 'employee_custody';
        }
        if (str_starts_with($uri, 'boat-revenues')) {
            return 'boat_revenues';
        }
        if (str_starts_with($uri, 'vehicle-revenues')) {
            return 'vehicle_revenues';
        }
        if (preg_match('/^(api\\/)?accounts(?:\\/|$)/', $uri)
            || str_starts_with($uri, 'templates/accounts')
            || $uri === 'api/next-movement-no') {
            return 'accounts';
        }
        if (preg_match('/^(api\\/)?journal(?:\\/|$)/', $uri) || str_starts_with($uri, 'templates/journal')) {
            return 'journal';
        }
        if (preg_match('/^(api\\/)?(ledger|trial-balance|budget|income-statement|balance-sheet|cash-flow)(?:\\/|$)/', $uri)) {
            return 'reports';
        }
        if (preg_match('/^(api\\/)?users(?:\\/|$)/', $uri)) {
            return 'users';
        }
        if (str_starts_with($uri, 'audit') || $uri === 'api/audit') {
            return 'audit';
        }
        if (str_starts_with($uri, 'settings')
            || str_starts_with($uri, 'api/settings')
            || str_starts_with($uri, 'backup')
            || str_starts_with($uri, 'backups')
            || str_starts_with($uri, 'api/backup')
            || str_starts_with($uri, 'api/backups')
            || str_starts_with($uri, 'restore/')
            || str_starts_with($uri, 'api/restore')) {
            return 'settings';
        }
        if (str_starts_with($uri, 'regions/') || $uri === 'regions' || str_starts_with($uri, 'api/regions') || str_starts_with($uri, 'api/region-')) {
            return 'regions';
        }
        if (str_ends_with($uri, '/region')) {
            return 'regions';
        }
        if (str_starts_with($uri, 'trips/hotels')
            || preg_match('#^api/trips/[^/]+/hotels$#', $uri)
            || preg_match('#^api/hotels/[^/]+/region$#', $uri)
            || $uri === 'api/trip-hotels') {
            return 'trip_hotels';
        }
        if (str_starts_with($uri, 'help')) {
            return 'reports';
        }
        if (str_starts_with($uri, 'operation-resources/')) {
            $resource = explode('/', substr($uri, strlen('operation-resources/')))[0];

            return [
                'trips' => 'trips',
                'hotels' => 'hotels',
                'trips-hotels' => 'trip_hotels',
                'suppliers' => 'suppliers',
                'companies' => 'companies',
                'extras' => 'extras',
                'company-prices' => 'company_prices',
                'boats' => 'boats',
                'vehicles' => 'vehicles',
                'drivers' => 'drivers',
                'boat-drivers' => 'boat_drivers',
            ][$resource] ?? null;
        }

        return null;
    }

    public static function actionForRoute(string $method, ?string $routeName, string $uri = ''): string
    {
        $name = strtolower($routeName ?? '');
        if (str_ends_with($name, '.destroy') || str_contains($name, '.delete')) {
            return 'delete';
        }
        if (str_ends_with($name, '.store') || str_ends_with($name, '.create')) {
            return 'create';
        }
        if (str_ends_with($name, '.update') || str_ends_with($name, '.save')
            || str_ends_with($name, '.edit') || str_contains($name, '.restore')) {
            return 'update';
        }
        if ($name === 'create.operations' || $name === 'save.operations') {
            return 'create';
        }
        if ($name === 'cancel.operations') {
            return 'delete';
        }
        if ($name === 'company.prices') {
            return 'update';
        }
        if ($name === 'print.save') {
            return 'view';
        }
        if (str_ends_with($uri, '/import')) {
            return 'update';
        }
        if (str_contains($uri, 'bulk-delete') || str_ends_with($uri, '/wipe')) {
            return 'delete';
        }
        if (str_starts_with($uri, 'api/restore/')
            || preg_match('#/(post|unpost|restore|restore-file|deactivate|activate|deposit|save|budget|security|password)$#', $uri)
            || str_ends_with($uri, 'post-to-accounting')) {
            return 'update';
        }

        return match (strtoupper($method)) {
            'GET', 'HEAD' => 'view',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'create',
        };
    }
}
