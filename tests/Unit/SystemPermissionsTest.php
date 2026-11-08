<?php

namespace Tests\Unit;

use App\Support\SystemPermissions;
use Tests\TestCase;

class SystemPermissionsTest extends TestCase
{
    public function test_every_route_protected_by_system_permissions_has_a_module_mapping(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            if (! in_array('system-permission', $route->gatherMiddleware(), true)) {
                continue;
            }

            if (str_ends_with((string) $route->getName(), '.logout')) {
                continue;
            }

            $this->assertNotNull(
                SystemPermissions::moduleForRoute($route->uri()),
                "Unmapped system permission route: {$route->uri()}"
            );
        }
    }

    public function test_read_forms_and_mutations_use_their_expected_actions(): void
    {
        $this->assertSame(
            'create',
            SystemPermissions::actionForRoute('GET', 'operation-assignments.create', 'operation-assignments/create')
        );
        $this->assertSame(
            'create',
            SystemPermissions::actionForRoute('GET', 'create.operations', 'staff/operation/create/{trip}')
        );
        $this->assertSame(
            'delete',
            SystemPermissions::actionForRoute('POST', 'cancel.operations', 'staff/operation/cancel')
        );
        $this->assertSame(
            'update',
            SystemPermissions::actionForRoute('POST', null, 'api/journal/{id}/post')
        );
        $this->assertSame(
            'update',
            SystemPermissions::actionForRoute('POST', null, 'api/restore/{name}')
        );
    }
}
