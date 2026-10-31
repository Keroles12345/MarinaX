<?php

namespace Tests\Feature;

use App\Models\Trips;
use App\Models\VehicleType;
use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('operation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->unsignedBigInteger('operation_id');
            $table->string('type');
            $table->unsignedBigInteger('resource_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->date('assign_date');
            $table->timestamps();
            $table->unique(['operation_id', 'type', 'resource_id', 'assign_date']);
        });
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        DB::table('tenants')->insert([
            ['id' => 1, 'name' => 'First company'],
            ['id' => 2, 'name' => 'Second company'],
        ]);
        DB::table('trips')->insert([
            ['tenant_id' => 1, 'name' => 'First trip'],
            ['tenant_id' => 2, 'name' => 'Second trip'],
        ]);
        DB::table('vehicle_types')->insert([
            ['id' => 1, 'name' => 'Bus'],
            ['id' => 2, 'name' => 'Minibus'],
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        Schema::dropIfExists('vehicle_types');
        Schema::dropIfExists('operation_assignments');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    public function test_queries_and_writes_are_limited_to_the_current_company(): void
    {
        TenantContext::initialize(1);

        $this->assertSame(
            ['Bus', 'Minibus'],
            TenantDatabase::table('vehicle_types')->orderBy('id')->pluck('name')->all(),
        );
        $this->assertSame(
            ['Bus', 'Minibus'],
            VehicleType::query()->orderBy('id')->pluck('name')->all(),
        );
        $this->assertSame(
            ['First trip'],
            TenantDatabase::table('trips')->orderBy('id')->pluck('name')->all(),
        );
        $this->assertSame(
            ['First trip'],
            TenantDatabase::table('trips')
                ->where(fn ($query) => $query->where('name', 'First trip')->orWhere('name', 'Second trip'))
                ->orderBy('id')
                ->pluck('name')
                ->all(),
        );
        $this->assertFalse(
            TenantDatabase::table('trips as scoped_trips')->where('scoped_trips.name', 'Second trip')->exists(),
        );
        $this->assertSame(['First trip'], Trips::query()->pluck('name')->all());

        TenantDatabase::table('trips')->insert(['name' => 'New first-company trip']);

        $this->assertSame(
            1,
            (int) DB::table('trips')->where('name', 'New first-company trip')->value('tenant_id'),
        );

        TenantDatabase::table('trips')->where('id', 2)->update(['name' => 'Changed by the wrong company']);
        $this->assertSame('Second trip', DB::table('trips')->where('id', 2)->value('name'));

        Trips::query()->create(['name' => 'Eloquent first-company trip']);
        $this->assertSame(
            1,
            (int) DB::table('trips')->where('name', 'Eloquent first-company trip')->value('tenant_id'),
        );

        $validator = Validator::make(
            ['trip_id' => 2],
            ['trip_id' => 'tenant_exists:trips,id'],
        );
        $this->assertFalse($validator->passes());

        TenantContext::initialize(null);
        $this->assertSame([], TenantDatabase::table('trips')->pluck('name')->all());
    }

    public function test_tenant_database_adds_company_id_when_saving_operation_assignments(): void
    {
        TenantContext::initialize(1);

        TenantDatabase::table('operation_assignments')->insert([
            'operation_id' => 137,
            'type' => 'boat',
            'resource_id' => 11,
            'driver_id' => null,
            'assign_date' => '2026-10-26',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('operation_assignments', [
            'operation_id' => 137,
            'tenant_id' => 1,
            'type' => 'boat',
            'resource_id' => 11,
            'assign_date' => '2026-10-26',
        ]);
        $this->assertSame(
            1,
            TenantDatabase::table('operation_assignments')->where('operation_id', 137)->count(),
        );
    }
}
