<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(true);
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->foreignId('tenant_id')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });
        Schema::create('drives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('license_number')->nullable();
            $table->timestamp('create_at')->nullable();
            $table->string('status', 20)->default('available');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->date('LicenseDate')->nullable();
            $table->string('degreeOfLeave')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
        Schema::create('boats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('status')->default('active');
        });
        Schema::create('vehicle_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('vehicle_id');
            $table->foreignId('driver_id');
            $table->string('assignment_role')->default('master');
            $table->timestamps();
            $table->unique(['vehicle_id', 'driver_id']);
        });
        Schema::create('boat_drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('boat_id');
            $table->foreignId('driver_id');
            $table->string('assignment_role')->default('master');
            $table->timestamps();
        });
        Schema::create('driver_boats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->foreignId('boat_id');
            $table->string('license_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('key');
            $table->text('value')->nullable();
        });
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('user')->nullable();
            $table->string('action')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        foreach (['audit_log', 'settings', 'driver_boats', 'boat_drivers', 'vehicle_drivers', 'vehicles', 'boats', 'drives', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_admin_can_manage_vehicle_and_boat_drivers_for_its_company(): void
    {
        $tenantId = DB::table('tenants')->insertGetId(['name' => 'Driver Company', 'status' => true]);
        $otherTenantId = DB::table('tenants')->insertGetId(['name' => 'Other Driver Company', 'status' => true]);
        $vehicleId = DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Company Bus',
            'status' => 'active',
        ]);
        $boatId = DB::table('boats')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Company Boat',
            'is_active' => true,
        ]);
        DB::table('vehicles')->insert([
            'tenant_id' => $otherTenantId,
            'name' => 'Private Bus',
            'status' => 'active',
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Driver Admin',
            'username' => 'driver-admin',
            'email' => 'driver-admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('accounting.drivers'))
            ->assertOk()
            ->assertSee('nav-page-search-input')
            ->assertSee('سائقو السيارات والأتوبيسات')
            ->assertSee('Company Bus')
            ->assertDontSee('Private Bus');

        $this->get(route('accounting.boat-drivers'))
            ->assertOk()
            ->assertSee('سائقو المراكب');

        $this->post(route('accounting.drivers.store'), [
            'name' => 'Vehicle Driver',
            'phone' => '01000000000',
            'license_number' => 'LIC-101',
            'vehicle_ids' => [$vehicleId],
        ])->assertRedirect(route('accounting.drivers'));

        $driverId = (int) DB::table('drives')->where('name', 'Vehicle Driver')->value('id');
        $this->assertDatabaseHas('drives', [
            'id' => $driverId,
            'tenant_id' => $tenantId,
            'status' => 'available',
        ]);
        $this->assertDatabaseHas('vehicle_drivers', [
            'tenant_id' => $tenantId,
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
        ]);

        $this->put(route('accounting.drivers.update', $driverId), [
            'name' => 'Updated Vehicle Driver',
            'phone' => '01111111111',
            'status' => 'available',
            'vehicle_ids' => [],
        ])->assertRedirect(route('accounting.drivers'));
        $this->assertDatabaseMissing('vehicle_drivers', ['driver_id' => $driverId]);

        $this->delete(route('accounting.drivers.destroy', $driverId))
            ->assertRedirect(route('accounting.drivers'));
        $this->assertDatabaseHas('drives', ['id' => $driverId, 'status' => 'inactive']);
        $this->put(route('accounting.drivers.restore', $driverId))
            ->assertRedirect(route('accounting.drivers'));
        $this->assertDatabaseHas('drives', ['id' => $driverId, 'status' => 'available']);

        $this->post(route('accounting.boat-drivers.store'), [
            'name' => 'Boat Driver',
            'phone' => '01222222222',
            'license_number' => 'BOAT-LIC-1',
            'boat_id' => $boatId,
        ])->assertRedirect(route('accounting.boat-drivers'));

        $boatDriverId = (int) DB::table('driver_boats')->where('name', 'Boat Driver')->value('id');
        $this->get(route('accounting.boat-drivers'))->assertSee('Boat Driver');
        $this->assertDatabaseHas('driver_boats', [
            'id' => $boatDriverId,
            'tenant_id' => $tenantId,
            'boat_id' => $boatId,
            'is_active' => true,
        ]);
        $this->assertSame(
            ['Boat Driver'],
            \App\Models\Boat::query()->findOrFail($boatId)->boatDrivers->pluck('name')->all(),
        );

        $this->put(route('accounting.boat-drivers.update', $boatDriverId), [
            'name' => 'Updated Boat Driver',
            'boat_id' => $boatId,
            'phone' => '01333333333',
            'license_number' => 'BOAT-LIC-2',
        ])->assertRedirect(route('accounting.boat-drivers'));

        $this->delete(route('accounting.boat-drivers.destroy', $boatDriverId))
            ->assertRedirect(route('accounting.boat-drivers'));
        $this->assertDatabaseHas('driver_boats', ['id' => $boatDriverId, 'is_active' => false]);
        $this->put(route('accounting.boat-drivers.restore', $boatDriverId))
            ->assertRedirect(route('accounting.boat-drivers'));
        $this->assertDatabaseHas('driver_boats', ['id' => $boatDriverId, 'is_active' => true]);
    }
}
