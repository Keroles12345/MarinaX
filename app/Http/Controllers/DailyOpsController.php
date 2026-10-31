<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\Boat_Driver;
use App\Models\Companies;
use App\Models\OperationAssignment;
use App\Models\Vehicle;
use App\Models\Supplier;
use App\Models\operations;
use App\Services\StaffRegionAccess;
use App\Services\OperationRevenueAccountService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DailyOpsController extends Controller
{
    public function index(Request $request)
    {
        $date = Carbon::parse($request->input('date', today()->toDateString()))->toDateString();
        $staff = Auth::guard('staff')->user();
        $regionIds = StaffRegionAccess::regionIds($staff);

        $operations = StaffRegionAccess::operationQuery($staff)
            ->with(['tripHotel.hotel', 'trip'])
            ->whereDate('trip_date', $date)
            // Remove this line to include non-complete operations in the planner.
            ->whereIn('status', ['complete', 'cancel'])
            ->orderBy('time_pickup')
            ->orderBy('trip_time')
            ->get()
            ->keyBy('id');

        $boats = StaffRegionAccess::boatQuery($staff)->with(['boatDrivers', 'supplier', 'region'])->orderBy('name')->get();
        $vehicles = StaffRegionAccess::vehicleQuery($staff)->with(['vehicleDrivers.driver', 'region'])->orderBy('name')->get();
        $suppliers = Supplier::query()->where('status', true)->orderBy('name')->get();
        $companies = Companies::query()->where('status', true)->orderBy('name')->get();
        $regions = $staff->regions()->where('regions.is_active', true)->orderBy('regions.name')->get(['regions.id', 'regions.name']);

        $assignments = OperationAssignment::query()
            ->whereDate('assign_date', $date)
            ->whereIn('operation_id', $operations->keys())
            ->get()
            ->groupBy(fn (OperationAssignment $assignment) => $assignment->type . ':' . $assignment->resource_id);

        return view('staff.daily-ops.index', compact('date', 'operations', 'boats', 'vehicles', 'assignments', 'suppliers', 'companies', 'regions'));
    }

    public function storeBoat(Request $request, OperationRevenueAccountService $revenueAccounts)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('boats', 'code')->where('tenant_id', \App\Support\TenantContext::id())],
            'ownership_type' => ['required', Rule::in(['internal', 'external'])],
            'supplier_id' => ['nullable', 'required_if:ownership_type,external', 'integer', Rule::exists('suppliers', 'id')->where('status', true)->where('tenant_id', \App\Support\TenantContext::id())],
            'supplier_price' => ['nullable', 'required_if:ownership_type,external', 'numeric', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1'],
            'region_id' => ['required', 'integer', Rule::in(StaffRegionAccess::regionIds(Auth::guard('staff')->user()))],
        ]);

        if ($validated['ownership_type'] === 'internal') {
            $validated['supplier_id'] = null;
            $validated['supplier_price'] = null;
        } else {
            Supplier::query()->where('status', true)->findOrFail($validated['supplier_id']);
        }

        DB::transaction(function () use ($validated, $revenueAccounts): void {
            $boat = Boat::create($validated + ['is_active' => true]);
            $revenueAccounts->ensure('boat', (int) $boat->id, $boat->name, $boat->ownership_type);
        });

        return redirect()->route('daily-ops.index')->with('status', 'Boat added successfully.');
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'assignments' => ['present', 'array'],
            'assignments.*.operation_id' => ['required', 'integer'],
            'assignments.*.type' => ['required', Rule::in(['boat', 'vehicle'])],
            'assignments.*.resource_id' => ['required', 'integer'],
            'assignments.*.driver_id' => ['nullable', 'integer'],
        ]);

        $date = $validated['date'];
        $rows = $validated['assignments'];

        // Validate resource/driver relationships before modifying the existing plan.
        foreach ($rows as $row) {
            $resourceModel = $row['type'] === 'boat' ? Boat::class : Vehicle::class;
            $driverId = $row['driver_id'] ?? null;
            $staff = Auth::guard('staff')->user();
            $operation = StaffRegionAccess::operationQuery($staff)
                ->join('trips', 'trips.id', '=', 'operations.id_trip')
                ->where('operations.id', $row['operation_id'])
                ->first(['operations.id', 'trips.region_id']);
            if (! $operation) {
                return response()->json(['message' => 'One of the selected operations is outside your assigned regions.'], 422);
            }

            $resourceQuery = $resourceModel::whereKey($row['resource_id']);
            $resourceQuery->where('region_id', $operation->region_id);
            if ($row['type'] === 'boat') {
                $resourceQuery->where('is_active', true);
            } else {
                $resourceQuery->where('status', 'active');
            }
            if (! $resourceQuery->exists()) {
                return response()->json(['message' => 'One of the selected resources no longer exists.'], 422);
            }

            $driverIsAttached = $row['type'] === 'boat'
                ? Boat_Driver::query()
                    ->whereKey($driverId)
                    ->where('boat_id', $row['resource_id'])
                    ->where('is_active', true)
                    ->exists()
                : \App\Support\TenantDatabase::table('vehicle_drivers')
                    ->where('vehicle_id', $row['resource_id'])
                    ->where('driver_id', $driverId)
                    ->exists();

            if ($driverId !== null && ! $driverIsAttached) {
                return response()->json(['message' => 'A selected driver is not assigned to that resource.'], 422);
            }
        }

        $hasDuplicate = collect($rows)->countBy(
            fn (array $row) => implode(':', [$row['operation_id'], $row['type'], $row['resource_id']])
        )->contains(fn (int $count) => $count > 1);
        if ($hasDuplicate) {
            return response()->json(['message' => 'An operation cannot be added twice to the same resource.'], 422);
        }

        $operationIds = collect($rows)->pluck('operation_id')->unique();
        $staff = Auth::guard('staff')->user();
        $validOperationCount = StaffRegionAccess::operationQuery($staff)
            ->whereIn('id', $operationIds)
            ->whereDate('trip_date', $date)
            ->where('status', 'complete')
            ->count();

        if ($validOperationCount !== $operationIds->count()) {
            return response()->json(['message' => 'One or more operations are not available for the selected date.'], 422);
        }

        $scopedOperationIds = StaffRegionAccess::operationQuery($staff)
            ->whereDate('trip_date', $date)
            ->whereIn('status', ['complete', 'cancel'])
            ->pluck('operations.id');

        DB::transaction(function () use ($date, $rows, $scopedOperationIds) {
            operations::query()
                ->whereIn('id', $scopedOperationIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            DB::table('operation_assignments')
                ->whereDate('assign_date', $date)
                ->whereIn('operation_id', $scopedOperationIds)
                ->delete();

            if ($rows !== []) {
                $now = now();
                \App\Support\TenantDatabase::table('operation_assignments')->insert(array_map(fn (array $row) => [
                    'operation_id' => $row['operation_id'],
                    'type' => $row['type'],
                    'resource_id' => $row['resource_id'],
                    'driver_id' => $row['driver_id'] ?? null,
                    'assign_date' => $date,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $rows));
            }
        });

        return response()->json(['ok' => true]);
    }
}
