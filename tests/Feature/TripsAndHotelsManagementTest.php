<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TripsAndHotelsManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSystemPermissionTables();

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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->foreignId('region_id')->nullable();
            $table->time('time_trip')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->foreignId('region_id')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('trips_hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable();
            $table->foreignId('trip_id');
            $table->foreignId('hotel_id');
            $table->time('pickup_time')->nullable();
            $table->boolean('status')->default(true);
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
        foreach (['audit_log', 'settings', 'trips_hotels', 'hotels', 'trips', 'regions', 'users', 'tenants'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_admin_can_create_company_scoped_trip_and_hotel(): void
    {
        $tenantId = DB::table('tenants')->insertGetId(['name' => 'Travel Company', 'status' => true]);
        $regionId = DB::table('regions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Hurghada',
            'is_active' => true,
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Travel Admin',
            'username' => 'travel-admin',
            'email' => 'travel-admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);
        $this->grantAllSystemPermissions($admin);

        $this->actingAs($admin, 'admin')
            ->get(route('accounting.trips'))
            ->assertOk()
            ->assertSee('إدارة الرحلات')
            ->assertSee('إضافة رحلة')
            ->assertDontSee('إضافة فندق');

        $this->get(route('accounting.hotels'))
            ->assertOk()
            ->assertSee('إدارة الفنادق')
            ->assertSee('إضافة فندق')
            ->assertDontSee('إضافة رحلة');

        $this->get(route('accounting.trip-hotels'))
            ->assertOk()
            ->assertSee('trip-hotel-table')
            ->assertSee('وقت الاستلام');

        $this->get(route('accounting.trips-hotels'))
            ->assertRedirect(route('accounting.trips'));
        $this->get(route('accounting.trips-hotels', ['tab' => 'hotels', 'include_archived' => 1]))
            ->assertRedirect(route('accounting.hotels', ['include_archived' => 1]));

        $this->post(route('accounting.trips.store'), [
            '_form_type' => 'create-trip',
            'name' => 'Sea Trip',
            'region_id' => $regionId,
            'time_trip' => '08:30',
            'description' => 'Morning departure',
        ])->assertRedirect(route('accounting.trips'));

        $tripId = (int) DB::table('trips')->where('name', 'Sea Trip')->value('id');
        $this->assertDatabaseHas('trips', [
            'id' => $tripId,
            'tenant_id' => $tenantId,
            'region_id' => $regionId,
            'status' => true,
        ]);

        $this->post(route('accounting.hotels.store'), [
            '_form_type' => 'create-hotel',
            'name' => 'Sea View Hotel',
            'region_id' => $regionId,
            'phone' => '123456',
            'email' => 'hotel@example.test',
            'address' => 'Hurghada',
        ])->assertRedirect(route('accounting.hotels'));

        $this->assertDatabaseHas('hotels', [
            'tenant_id' => $tenantId,
            'name' => 'Sea View Hotel',
            'region_id' => $regionId,
            'status' => true,
        ]);
    }

    public function test_admin_cannot_assign_another_company_region_to_a_trip(): void
    {
        $tenantId = DB::table('tenants')->insertGetId(['name' => 'Travel Company A', 'status' => true]);
        $otherTenantId = DB::table('tenants')->insertGetId(['name' => 'Travel Company B', 'status' => true]);
        $otherRegionId = DB::table('regions')->insertGetId([
            'tenant_id' => $otherTenantId,
            'name' => 'Other Region',
            'is_active' => true,
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Travel Admin',
            'username' => 'travel-admin-two',
            'email' => 'travel-admin-two@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);
        $this->grantAllSystemPermissions($admin);

        $this->actingAs($admin, 'admin')
            ->from(route('accounting.trips'))
            ->post(route('accounting.trips.store'), [
                '_form_type' => 'create-trip',
                'name' => 'Cross Company Trip',
                'region_id' => $otherRegionId,
            ])
            ->assertRedirect(route('accounting.trips'))
            ->assertSessionHasErrors('region_id');

        $this->assertDatabaseMissing('trips', ['name' => 'Cross Company Trip']);
    }

    public function test_admin_can_set_a_pickup_time_for_each_trip_hotel_assignment(): void
    {
        $tenantId = DB::table('tenants')->insertGetId(['name' => 'Pickup Time Company', 'status' => true]);
        $regionId = DB::table('regions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Hurghada',
            'is_active' => true,
        ]);
        $tripId = DB::table('trips')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Morning Trip',
            'region_id' => $regionId,
            'status' => true,
        ]);
        $hotelOneId = DB::table('hotels')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Hotel One',
            'region_id' => $regionId,
            'status' => true,
        ]);
        $hotelTwoId = DB::table('hotels')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Hotel Two',
            'region_id' => $regionId,
            'status' => true,
        ]);
        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Pickup Admin',
            'username' => 'pickup-admin',
            'email' => 'pickup-admin@example.test',
            'password' => Hash::make('StrongPass123'),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => $tenantId,
        ]);
        $this->grantAllSystemPermissions($admin);

        $this->actingAs($admin, 'admin')
            ->put("/api/trips/{$tripId}/hotels", [
                'hotel_ids' => [$hotelOneId, $hotelTwoId],
                'hotel_times' => [
                    $hotelOneId => '07:15',
                    $hotelTwoId => '07:45',
                ],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('trips_hotels', [
            'tenant_id' => $tenantId,
            'trip_id' => $tripId,
            'hotel_id' => $hotelOneId,
            'pickup_time' => '07:15',
            'status' => true,
        ]);
        $this->assertDatabaseHas('trips_hotels', [
            'tenant_id' => $tenantId,
            'trip_id' => $tripId,
            'hotel_id' => $hotelTwoId,
            'pickup_time' => '07:45',
            'status' => true,
        ]);

        $this->get('/api/trip-hotels')
            ->assertOk()
            ->assertJsonFragment([
                'trip_id' => $tripId,
                'hotel_id' => $hotelOneId,
                'pickup_time' => '07:15',
            ]);

        $this->put("/api/trips/{$tripId}/hotels", [
            'hotel_ids' => [$hotelOneId],
        ])->assertOk();
        $this->assertDatabaseHas('trips_hotels', [
            'trip_id' => $tripId,
            'hotel_id' => $hotelOneId,
            'pickup_time' => '07:15',
            'status' => true,
        ]);

        $this->put("/api/trips/{$tripId}/hotels", [
            'hotel_ids' => [$hotelOneId],
            'hotel_times' => [$hotelOneId => null],
        ])->assertOk();
        $this->assertDatabaseHas('trips_hotels', [
            'trip_id' => $tripId,
            'hotel_id' => $hotelOneId,
            'pickup_time' => null,
            'status' => true,
        ]);

        $this->put("/api/trips/{$tripId}/hotels", [
            'hotel_ids' => [$hotelOneId],
            'hotel_times' => [$hotelOneId => 'not-a-time'],
        ])->assertSessionHasErrors("hotel_times.{$hotelOneId}");
    }
}
