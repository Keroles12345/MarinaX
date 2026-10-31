<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Region;
use App\Models\Trips;
use App\Models\Trips_hotels;
use App\Models\User;
use App\Services\StaffRegionAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffRegionAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('username')->nullable();
            $table->string('password');
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('staff_region_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('region_id');
            $table->timestamps();
        });
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->string('name');
            $table->time('time_trip')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('region_id')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('trips_hotels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_id');
            $table->unsignedBigInteger('hotel_id');
            $table->time('pickup_time')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_trip');
            $table->unsignedBigInteger('trip_hotel_id')->nullable();
            $table->string('status')->default('complete');
            $table->timestamps();
        });
        Schema::create('boats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->string('name');
            $table->string('status')->default('active');
        });
    }

    protected function tearDown(): void
    {
        foreach (['vehicles', 'boats', 'operations', 'trips_hotels', 'hotels', 'trips', 'staff_region_assignments', 'regions', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_staff_only_sees_trips_and_hotels_in_assigned_active_regions(): void
    {
        $staff = User::create([
            'name' => 'Regional Staff',
            'email' => 'regional-staff@example.test',
            'username' => 'regional-staff',
            'password' => 'password',
            'role' => 'staff',
            'status' => true,
        ]);

        $allowedRegion = Region::create(['name' => 'Allowed area', 'is_active' => true]);
        $otherRegion = Region::create(['name' => 'Other area', 'is_active' => true]);
        $inactiveRegion = Region::create(['name' => 'Inactive area', 'is_active' => false]);
        $staff->regions()->attach($allowedRegion->id);
        $staff->regions()->attach($inactiveRegion->id);

        $allowedTrip = Trips::create(['name' => 'Allowed trip', 'region_id' => $allowedRegion->id, 'status' => true]);
        $otherTrip = Trips::create(['name' => 'Other trip', 'region_id' => $otherRegion->id, 'status' => true]);
        $inactiveTrip = Trips::create(['name' => 'Inactive trip', 'region_id' => $inactiveRegion->id, 'status' => true]);
        $mismatchedTrip = Trips::create(['name' => 'Mismatched trip', 'region_id' => $allowedRegion->id, 'status' => true]);

        $allowedHotel = Hotel::create(['name' => 'Allowed hotel', 'region_id' => $allowedRegion->id, 'status' => true]);
        $otherHotel = Hotel::create(['name' => 'Other hotel', 'region_id' => $otherRegion->id, 'status' => true]);
        $inactiveHotel = Hotel::create(['name' => 'Inactive hotel', 'region_id' => $inactiveRegion->id, 'status' => true]);

        $allowedLink = Trips_hotels::create(['trip_id' => $allowedTrip->id, 'hotel_id' => $allowedHotel->id, 'status' => true]);
        $otherLink = Trips_hotels::create(['trip_id' => $otherTrip->id, 'hotel_id' => $otherHotel->id, 'status' => true]);
        $inactiveLink = Trips_hotels::create(['trip_id' => $inactiveTrip->id, 'hotel_id' => $inactiveHotel->id, 'status' => true]);
        $mismatchedLink = Trips_hotels::create(['trip_id' => $mismatchedTrip->id, 'hotel_id' => $otherHotel->id, 'status' => true]);

        $visibleTripIds = StaffRegionAccess::tripQuery($staff)->pluck('trips.id')->all();
        DB::table('operations')->insert([
            ['id_trip' => $allowedTrip->id, 'trip_hotel_id' => $allowedLink->id, 'status' => 'complete', 'created_at' => now(), 'updated_at' => now()],
            ['id_trip' => $otherTrip->id, 'trip_hotel_id' => $otherLink->id, 'status' => 'complete', 'created_at' => now(), 'updated_at' => now()],
            ['id_trip' => $inactiveTrip->id, 'trip_hotel_id' => $inactiveLink->id, 'status' => 'complete', 'created_at' => now(), 'updated_at' => now()],
            ['id_trip' => $mismatchedTrip->id, 'trip_hotel_id' => $mismatchedLink->id, 'status' => 'complete', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $visibleOperationIds = StaffRegionAccess::operationQuery($staff)->pluck('operations.id')->all();
        DB::table('boats')->insert([
            ['region_id' => $allowedRegion->id, 'name' => 'Allowed boat', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['region_id' => $otherRegion->id, 'name' => 'Other boat', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('vehicles')->insert([
            ['region_id' => $allowedRegion->id, 'name' => 'Allowed vehicle', 'status' => 'active'],
            ['region_id' => $otherRegion->id, 'name' => 'Other vehicle', 'status' => 'active'],
        ]);

        $this->assertSame([$allowedTrip->id], $visibleTripIds);
        $this->assertSame([1], $visibleOperationIds);
        $this->assertSame(['Allowed boat'], StaffRegionAccess::boatQuery($staff)->pluck('name')->all());
        $this->assertSame(['Allowed vehicle'], StaffRegionAccess::vehicleQuery($staff)->pluck('name')->all());
        $this->assertTrue(StaffRegionAccess::canAccessTripHotel($allowedLink->id, $allowedTrip->id, $staff));
        $this->assertFalse(StaffRegionAccess::canAccessTripHotel($otherLink->id, $otherTrip->id, $staff));
        $this->assertFalse(StaffRegionAccess::canAccessTripHotel($inactiveLink->id, $inactiveTrip->id, $staff));
        $this->assertFalse(StaffRegionAccess::canAccessTripHotel($mismatchedLink->id, $mismatchedTrip->id, $staff));
    }
}
