<?php

namespace Tests\Unit;

use App\Services\EmployeeCustodyService;
use PHPUnit\Framework\TestCase;

class EmployeeCustodyServiceTest extends TestCase
{
    public function test_collected_amount_is_converted_to_the_base_currency(): void
    {
        $service = new EmployeeCustodyService();

        self::assertSame(500.0, $service->collectedAmount(1000, 200, 100));
        self::assertSame(0.0, $service->collectedAmount(1000, 0, 100));
    }

    public function test_collected_amount_is_capped_at_the_sale_total(): void
    {
        $service = new EmployeeCustodyService();

        self::assertSame(1000.0, $service->collectedAmount(1000, 200, 250));
    }
}
