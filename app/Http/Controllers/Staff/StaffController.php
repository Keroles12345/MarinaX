<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\Boat_Driver;
use App\Models\departments;
use App\Models\Drive;
use App\Models\OperationAssignment;
use App\Models\Vehicle;
use App\Services\StaffRegionAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\TenantContext;

class StaffController extends Controller
{


public function loginn(Request $request)
{
    TenantContext::clear();

    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $guard = Auth::guard('staff');
    if (!$guard->attempt($credentials, $request->boolean('remember'))) {
        return back()
            ->withInput($request->except('password'))
            ->withErrors([
                'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
            ]);
    }

    $request->session()->regenerate();

    $user = $guard->user();

    if (! $user->status) {
        $guard->logout();
        $request->session()->regenerate();

        return back()
            ->withInput($request->except('password'))
            ->withErrors(['error' => 'هذا الحساب موقوف']);
    }

    if (! $user->tenant_id || ! $user->tenant?->status) {
        $guard->logout();
        $request->session()->regenerate();

        return back()
            ->withInput($request->except('password'))
            ->withErrors(['error' => 'الشركة المرتبط بها المستخدم غير مفعلة']);
    }

    if ($user->role !== 'staff') {
        $guard->logout();
        $request->session()->regenerate();

        return back()
            ->withInput($request->except('password'))
            ->withErrors([
                'error' => 'هذا المستخدم غير موظف',
            ]);
    }

    Auth::shouldUse('staff');

    return redirect()->route('dashboard');
}
public function showdashboard(){
    $staff = Auth::guard('staff')->user();
    $regions = $staff->regions()
        ->where('regions.is_active', true)
        ->orderBy('regions.name')
        ->get(['regions.id', 'regions.name']);
    $operations = StaffRegionAccess::operationQuery($staff)
        ->with('tripHotel.hotel.region')
        ->orderByDesc('created_at')
        ->limit(10)
        ->get();
    $operationCount = StaffRegionAccess::operationQuery($staff)->count();

    return view('staff.index', compact('regions', 'operations', 'operationCount'));
}
public function showPrint(Request $request)
{
    $query = OperationAssignment::query()
        ->select(
            'operation_assignments.*',
            'operations.booking_id',
            'operations.trip_date',
            'operations.trip_time',
            'operations.time_pickup',
            'hotels.name as hotel_name',
            'trips_hotels.pickup_time'
        )
        ->join(
            'operations',
            'operations.id',
            '=',
            'operation_assignments.operation_id'
        )
        ->leftJoin(
            'hotels',
            'hotels.id',
            '=',
            'operations.trip_hotel_id'
        )
        ->leftJoin('trips_hotels', function ($join) {
            $join->on('trips_hotels.hotel_id', '=', 'hotels.id')
                 ->on('trips_hotels.trip_id', '=', 'operations.id_trip');
        })
            ->whereIn(
                'operation_assignments.operation_id',
                StaffRegionAccess::operationQuery(Auth::guard('staff')->user())->select('operations.id')
            )
        ->when(
            $request->filled('date'),
            fn ($q) => $q->whereDate(
                'operation_assignments.assign_date',
                $request->input('date')
            )
        )
        ->when(
            $request->filled('type'),
            fn ($q) => $q->where(
                'operation_assignments.type',
                $request->input('type')
            )
        )
        ->when(
            $request->filled('search'),
            fn ($q) => $q->where(
                'operations.booking_id',
                'like',
                '%' . $request->input('search') . '%'
            )
        )
        ->orderByDesc('operation_assignments.assign_date')
        ->orderByDesc('operation_assignments.id');

    // كل النتائج المطلوبة للطباعة
    $assignments = $query->get();
$groupedAssignments = $assignments->groupBy(function ($assignment) {
    return $assignment->type . ':' . $assignment->resource_id;
});
    // Boat IDs
    $boatIds = $assignments
        ->where('type', 'boat')
        ->pluck('resource_id')
        ->unique();

    // Vehicle IDs
    $vehicleIds = $assignments
        ->where('type', 'vehicle')
        ->pluck('resource_id')
        ->unique();

    // Resource names
    $resourceNames = [];

    Boat::whereIn('id', $boatIds)
        ->whereIn('region_id', StaffRegionAccess::regionIds(Auth::guard('staff')->user()))
        ->get(['id', 'name'])
        ->each(function ($item) use (&$resourceNames) {
            $resourceNames['boat:' . $item->id] = $item->name;
        });

    Vehicle::whereIn('id', $vehicleIds)
        ->whereIn('region_id', StaffRegionAccess::regionIds(Auth::guard('staff')->user()))
        ->get(['id', 'name'])
        ->each(function ($item) use (&$resourceNames) {
            $resourceNames['vehicle:' . $item->id] = $item->name;
        });

    // Driver IDs
    $boatDriverIds = $assignments
        ->where('type', 'boat')
        ->pluck('driver_id')
        ->filter()
        ->unique();

    $vehicleDriverIds = $assignments
        ->where('type', 'vehicle')
        ->pluck('driver_id')
        ->filter()
        ->unique();

    // Driver names
    $driverNames = [
        'boat' => Boat_Driver::whereIn('id', $boatDriverIds)
            ->pluck('name', 'id'),

        'vehicle' => Drive::whereIn('id', $vehicleDriverIds)
            ->pluck('name', 'id'),
    ];

    // PDF
    $pdf = Pdf::loadView(
        'staff.Operations.printReports',
        compact(
            'groupedAssignments',
            'resourceNames',
            'driverNames'
        )
        );

$bookingId = date('Y-m-d H-i-s');

return $pdf->download($bookingId . '.pdf');

}
    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->regenerate();

        return redirect()->route('staff.login');
    }


}
