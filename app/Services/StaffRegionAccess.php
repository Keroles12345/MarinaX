<?php

namespace App\Services;

use App\Models\Trips;
use App\Models\User;
use App\Models\operations;
use App\Models\Boat;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class StaffRegionAccess
{
    public static function regionIds(User $staff): array
    {
        return $staff->regions()
            ->where('regions.is_active', true)
            ->pluck('regions.id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    public static function operations(Builder $query, User $staff): Builder
    {
        $regionIds = self::regionIds($staff);

        $accessibleOperationIds = \App\Support\TenantDatabase::table('operations as scoped_operations')
            ->join('trips as scoped_trips', 'scoped_trips.id', '=', 'scoped_operations.id_trip')
            ->join('trips_hotels as scoped_trip_hotels', 'scoped_trip_hotels.id', '=', 'scoped_operations.trip_hotel_id')
            ->join('hotels as scoped_hotels', 'scoped_hotels.id', '=', 'scoped_trip_hotels.hotel_id')
            ->whereIn('scoped_trips.region_id', $regionIds)
            ->whereColumn('scoped_hotels.region_id', 'scoped_trips.region_id')
            ->where('scoped_trips.status', true)
            ->where('scoped_trip_hotels.status', true)
            ->where('scoped_hotels.status', true)
            ->select('scoped_operations.id');

        return $query->whereIn('operations.id', $accessibleOperationIds);
    }

    public static function trips(Builder $query, User $staff): Builder
    {
        $regionIds = self::regionIds($staff);

        return $query->whereIn('trips.region_id', $regionIds)
            ->whereHas('tripHotels', static function (Builder $tripHotelQuery) use ($regionIds) {
            $tripHotelQuery->where('status', true)
                ->whereHas('hotel', static function (Builder $hotelQuery) use ($regionIds) {
                    $hotelQuery->where('hotels.status', true)
                        ->whereIn('hotels.region_id', $regionIds)
                        ->whereColumn('hotels.region_id', 'trips.region_id');
                });
        });
    }

    public static function canAccessTripHotel(int $tripHotelId, int $tripId, User $staff): bool
    {
        $regionIds = self::regionIds($staff);

        $tripRegionId = Trips::query()
            ->whereKey($tripId)
            ->whereIn('region_id', $regionIds)
            ->value('region_id');
        if (! $tripRegionId) {
            return false;
        }

        return \App\Models\Trips_hotels::query()
            ->whereKey($tripHotelId)
            ->where('trip_id', $tripId)
            ->where('status', true)
            ->whereHas('hotel', static function (Builder $hotelQuery) use ($regionIds) {
                $hotelQuery->where('hotels.status', true)
                    ->whereIn('hotels.region_id', $regionIds);
            })
            ->whereHas('hotel', static function (Builder $hotelQuery) use ($tripRegionId) {
                $hotelQuery->where('hotels.region_id', $tripRegionId);
            })
            ->exists();
    }

    public static function operationQuery(User $staff): Builder
    {
        return self::operations(operations::query(), $staff);
    }

    public static function tripQuery(User $staff): Builder
    {
        return self::trips(Trips::query(), $staff);
    }

    public static function boatQuery(User $staff): Builder
    {
        return Boat::query()->where('is_active', true)
            ->whereIn('region_id', self::regionIds($staff));
    }

    public static function vehicleQuery(User $staff): Builder
    {
        return Vehicle::query()->where('status', 'active')
            ->whereIn('region_id', self::regionIds($staff));
    }
}
