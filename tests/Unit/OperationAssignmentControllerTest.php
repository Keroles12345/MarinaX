<?php

namespace Tests\Unit;

use App\Http\Controllers\OperationAssignmentController;
use App\Services\OperationRevenueAccountService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class OperationAssignmentControllerTest extends TestCase
{
    public function test_operation_revenue_is_split_evenly_and_remainder_cents_are_preserved(): void
    {
        $method = new ReflectionMethod(OperationAssignmentController::class, 'splitAmountCents');
        $controller = new OperationAssignmentController();

        self::assertSame([334, 333, 333], $method->invoke($controller, 1000, 3));
        self::assertSame([1, 0], $method->invoke($controller, 1, 2));
    }

    public function test_external_resource_collections_go_to_cash_instead_of_employee_custody(): void
    {
        $method = new ReflectionMethod(OperationAssignmentController::class, 'shouldUseEmployeeCustody');
        $controller = new OperationAssignmentController();

        self::assertFalse($method->invoke($controller, true, 'staff'));
        self::assertTrue($method->invoke($controller, false, 'staff'));
        self::assertFalse($method->invoke($controller, false, 'admin'));
    }

    public function test_vehicle_revenue_account_is_not_reused_for_a_boat_with_the_same_id(): void
    {
        $method = new ReflectionMethod(OperationRevenueAccountService::class, 'isResourceAccount');
        $service = new OperationRevenueAccountService();

        self::assertFalse($method->invoke($service, 'إيرادات سيارة - حافلة (#3)', 'boat', 3));
        self::assertTrue($method->invoke($service, 'إيرادات مركب - دولفين (#3)', 'boat', 3));
        self::assertFalse($method->invoke($service, 'إيرادات مركب - دولفين (#3)', 'vehicle', 3));
        self::assertTrue($method->invoke($service, 'إيرادات سيارة - حافلة (#3)', 'vehicle', 3));
    }
}
