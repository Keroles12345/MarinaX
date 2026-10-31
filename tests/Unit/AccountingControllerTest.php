<?php

namespace Tests\Unit;

use App\Http\Controllers\AccountingController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class AccountingControllerTest extends TestCase
{
    public function test_account_and_journal_types_match_the_accounting_interface(): void
    {
        $controller = new ReflectionClass(AccountingController::class);

        self::assertSame(
            ['أصول', 'خصوم', 'حقوق ملكية', 'إيرادات', 'مصروفات', 'أخرى'],
            $controller->getReflectionConstant('ACCOUNT_TYPES')->getValue()
        );
        self::assertSame(
            ['عادي', 'تسوية', 'افتتاحي', 'إقفال'],
            $controller->getReflectionConstant('ENTRY_TYPES')->getValue()
        );
    }

    public function test_budget_actuals_net_expenses_and_revenue_reversals(): void
    {
        $method = new ReflectionMethod(AccountingController::class, 'budgetActual');
        $controller = new AccountingController();

        self::assertSame(80.0, $method->invoke($controller, 'مصروفات', 100.0, 20.0));
        self::assertSame(195.0, $method->invoke($controller, 'إيرادات', 5.0, 200.0));
        self::assertSame(80.0, $method->invoke($controller, 'أصول', 100.0, 20.0));
    }
}
