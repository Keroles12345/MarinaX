<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\Boat_Driver;
use App\Models\Drive;
use App\Models\OperationAssignment;
use App\Models\Vehicle;
use App\Models\operations;
use App\Services\OperationRevenueAccountService;
use App\Services\EmployeeCustodyService;
use App\Services\StaffRegionAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OperationAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = OperationAssignment::query()
            ->select(
                'operation_assignments.*',
                'operations.booking_id',
                'operations.trip_date',
                'operations.trip_time',
                'operations.time_pickup',
                'operations.totalamount',
                'operations.accounting_entry_id',
                'operations.status as operation_status',
            )
            ->join('operations', 'operations.id', '=', 'operation_assignments.operation_id')
            ->whereIn(
                'operations.id',
                StaffRegionAccess::operationQuery(Auth::guard('staff')->user())->select('operations.id')
            )
            ->when($request->filled('date'), fn ($q) => $q->whereDate('operation_assignments.assign_date', $request->input('date')))
            ->when($request->filled('type'), fn ($q) => $q->where('operation_assignments.type', $request->input('type')))
            ->when($request->filled('search'), fn ($q) => $q->where('operations.booking_id', 'like', '%' . $request->input('search') . '%'))
            ->orderByDesc('operation_assignments.assign_date')->orderByDesc('operation_assignments.id');

        $assignments = $query->paginate(15)->withQueryString();
        $operationIds = $assignments->getCollection()->pluck('operation_id')->unique();
        $boatIds = $assignments->getCollection()->where('type', 'boat')->pluck('resource_id')->unique();
        $vehicleIds = $assignments->getCollection()->where('type', 'vehicle')->pluck('resource_id')->unique();
        $resourceNames = [];
        Boat::whereIn('id', $boatIds)->get(['id', 'name'])->each(fn ($item) => $resourceNames['boat:' . $item->id] = $item->name);
        Vehicle::whereIn('id', $vehicleIds)->get(['id', 'name'])->each(fn ($item) => $resourceNames['vehicle:' . $item->id] = $item->name);
        $externalOperationIds = \App\Support\TenantDatabase::table('operation_assignments as oa')
            ->leftJoin('boats as b', function ($join) {
                $join->on('b.id', '=', 'oa.resource_id')->where('oa.type', 'boat');
            })
            ->leftJoin('vehicles as v', function ($join) {
                $join->on('v.id', '=', 'oa.resource_id')->where('oa.type', 'vehicle');
            })
            ->whereRaw("CASE WHEN oa.type = 'boat' THEN b.ownership_type ELSE v.ownership_type END = 'external'")
            ->whereIn('oa.operation_id', $operationIds)
            ->distinct()
            ->pluck('oa.operation_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $boatDriverIds = $assignments->where('type', 'boat')->pluck('driver_id')->filter()->unique();
        $vehicleDriverIds = $assignments->where('type', 'vehicle')->pluck('driver_id')->filter()->unique();
        $driverNames = [
            'boat' => Boat_Driver::whereIn('id', $boatDriverIds)->pluck('name', 'id'),
            'vehicle' => Drive::whereIn('id', $vehicleDriverIds)->pluck('name', 'id'),
        ];
        $regions = \App\Support\TenantDatabase::table('regions')
            ->whereIn('id', StaffRegionAccess::regionIds(Auth::guard('staff')->user()))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name');

        return view('staff.operation-assignments.index', compact('assignments', 'resourceNames', 'driverNames', 'externalOperationIds', 'regions'));
    }

    public function postToAccounting(
        Request $request,
        OperationRevenueAccountService $revenueAccounts,
        EmployeeCustodyService $custodyAccounts,
    )
    {
        $data = $request->validate([
            'operation_ids' => ['required', 'array', 'min:1', 'max:100'],
            'operation_ids.*' => ['required', 'integer', 'distinct', 'tenant_exists:operations,id'],
            'region' => ['required', 'string', 'tenant_exists:regions,name'],
        ]);

        $summary = DB::transaction(function () use ($data, $revenueAccounts, $custodyAccounts): array {
            $region = \App\Support\TenantDatabase::table('regions')->where('name', $data['region'])->where('is_active', true)->first();
            if (! $region) {
                throw ValidationException::withMessages(['region' => 'المنطقة المحددة غير مفعّلة. اختر منطقة مفعّلة.']);
            }

            $operations = operations::query()
                ->whereIn('id', $data['operation_ids'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($operations->count() !== count($data['operation_ids'])) {
                throw ValidationException::withMessages(['operation_ids' => 'تعذر العثور على إحدى العمليات المحددة.']);
            }

            $staff = Auth::guard('staff')->user();
            $accessibleCount = StaffRegionAccess::operationQuery($staff)
                ->whereIn('operations.id', $data['operation_ids'])
                ->count();
            $inRegionCount = \App\Support\TenantDatabase::table('operations')
                ->join('trips_hotels', 'trips_hotels.id', '=', 'operations.trip_hotel_id')
                ->join('hotels', 'hotels.id', '=', 'trips_hotels.hotel_id')
                ->whereIn('operations.id', $data['operation_ids'])
                ->where('hotels.region_id', $region->id)
                ->count();
            if ($accessibleCount !== count($data['operation_ids']) || $inRegionCount !== count($data['operation_ids'])) {
                throw ValidationException::withMessages([
                    'operation_ids' => 'يجب أن تكون كل العمليات المحددة من منطقة مسموح بها ومطابقة للمنطقة المحاسبية.',
                ]);
            }

            $cashAccountId = $custodyAccounts->cashAccountId();
            $receivablesAccountId = $this->accountId('1201', 'العملاء (المدينون)', 'أصول');
            $posted = ['internal' => 0, 'external' => 0];
            $employees = \App\Support\TenantDatabase::table('users')
                ->whereIn('id', $operations->pluck('by_created')->filter()->unique())
                ->get(['id', 'name', 'role'])
                ->keyBy('id');

            foreach ($operations as $operation) {
                if ($operation->status !== 'complete') {
                    throw ValidationException::withMessages([
                        'operation_ids' => "العملية {$operation->booking_id} غير مكتملة ولا يمكن ترحيلها.",
                    ]);
                }
                if ($operation->accounting_entry_id) {
                    throw ValidationException::withMessages([
                        'operation_ids' => "العملية {$operation->booking_id} سبق ترحيلها إلى الحسابات.",
                    ]);
                }
                if (! OperationAssignment::where('operation_id', $operation->id)->exists()) {
                    throw ValidationException::withMessages([
                        'operation_ids' => "العملية {$operation->booking_id} لا يوجد لها مورد مُعيّن.",
                    ]);
                }

                $amount = round((float) $operation->totalamount, 2);
                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'operation_ids' => "العملية {$operation->booking_id} ليس لها مبلغ صالح بالعملة الأساسية.",
                    ]);
                }

                $resources = \App\Support\TenantDatabase::table('operation_assignments as oa')
                    ->leftJoin('boats as b', function ($join) {
                        $join->on('b.id', '=', 'oa.resource_id')->where('oa.type', 'boat');
                    })
                    ->leftJoin('vehicles as v', function ($join) {
                        $join->on('v.id', '=', 'oa.resource_id')->where('oa.type', 'vehicle');
                    })
                    ->where('oa.operation_id', $operation->id)
                    ->whereIn('oa.type', ['boat', 'vehicle'])
                    ->select(
                        'oa.type as resource_type',
                        'oa.resource_id',
                        DB::raw("CASE WHEN oa.type = 'boat' THEN b.name ELSE v.name END as resource_name"),
                        DB::raw("CASE WHEN oa.type = 'boat' THEN b.ownership_type ELSE v.ownership_type END as ownership_type"),
                    )
                    ->distinct()
                    ->orderBy('oa.type')
                    ->orderBy('oa.resource_id')
                    ->get();
                $isExternal = $resources->contains(fn ($resource) => $resource->ownership_type === 'external');

                $operationTotal = (float) $operation->amount_total;
                $paidRatio = $operationTotal > 0
                    ? min(max((float) $operation->amount_paid / $operationTotal, 0), 1)
                    : 0;
                $paidAmount = round($amount * $paidRatio, 2);
                $receivableAmount = round($amount - $paidAmount, 2);
                $paidAccountId = $cashAccountId;
                $employee = $employees->get($operation->by_created);
                if ($paidAmount > 0 && $this->shouldUseEmployeeCustody($isExternal, $employee?->role)) {
                    $paidAccountId = $custodyAccounts->employeeAccountId((int) $employee->id, $employee->name);
                }
                $revenueLines = [];
                if ($resources->isEmpty()) {
                    $revenueLines[] = [
                        'account_id' => $this->accountId('4103', 'إيرادات عمليات داخلية', 'إيرادات'),
                        'amount' => $amount,
                    ];
                } else {
                    $sharesInCents = $this->splitAmountCents((int) round($amount * 100), $resources->count());
                    foreach ($resources->values() as $index => $resource) {
                        $shareCents = $sharesInCents[$index];
                        if ($shareCents === 0) {
                            continue;
                        }
                        $revenueLines[] = [
                            'account_id' => $revenueAccounts->ensure(
                                $resource->resource_type,
                                (int) $resource->resource_id,
                                $resource->resource_name,
                                $resource->ownership_type,
                            ),
                            'amount' => $shareCents / 100,
                        ];
                    }
                }

                $movementNo = 'OPS-'.$operation->id;
                if (\App\Support\TenantDatabase::table('journal_entries')->where('movement_no', $movementNo)->exists()) {
                    throw ValidationException::withMessages([
                        'operation_ids' => "رقم الحركة {$movementNo} مستخدم مسبقًا؛ لم يتم ترحيل العملية.",
                    ]);
                }

                $now = now();
                $resourceNames = $resources->pluck('resource_name')->implode('، ');
                $description = 'إيراد عملية '.$operation->booking_id;
                if ($paidAccountId !== $cashAccountId) {
                    $description .= ' - تحصيل بعهدة '.$employee->name;
                }
                if ($resources->isEmpty()) {
                    $description .= ' - تشغيل داخلي';
                } else {
                    $description .= ' - '.$resourceNames;
                }
                $entryId = \App\Support\TenantDatabase::table('journal_entries')->insertGetId([
                    'movement_no' => $movementNo,
                    'entry_date' => $operation->trip_date->toDateString(),
                    'description' => $description,
                    'region' => $region->name,
                    'status' => 'posted',
                    'entry_type' => 'عادي',
                    'created_by' => auth()->id(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $lines = [];
                if ($paidAmount > 0) {
                    $lines[] = ['entry_id' => $entryId, 'account_id' => $paidAccountId, 'debit' => $paidAmount, 'credit' => 0];
                }
                if ($receivableAmount > 0) {
                    $lines[] = ['entry_id' => $entryId, 'account_id' => $receivablesAccountId, 'debit' => $receivableAmount, 'credit' => 0];
                }
                foreach ($revenueLines as $revenueLine) {
                    $lines[] = [
                        'entry_id' => $entryId,
                        'account_id' => $revenueLine['account_id'],
                        'debit' => 0,
                        'credit' => $revenueLine['amount'],
                    ];
                }
                \App\Support\TenantDatabase::table('journal_lines')->insert($lines);
                operations::whereKey($operation->id)->update(['accounting_entry_id' => $entryId]);
                $posted[$isExternal ? 'external' : 'internal']++;
                \App\Support\TenantDatabase::table('audit_log')->insert([
                    'user' => auth()->user()->name,
                    'action' => 'ترحيل إيراد عملية',
                    'details' => $operation->booking_id.' — '.$amount.' — '.($isExternal ? 'مركب خارجي' : 'تشغيل داخلي').' — '.$movementNo,
                    'at' => $now,
                ]);
            }

            return $posted;
        });

        return redirect()->route('operation-assignments.index')
            ->with('status', "تم ترحيل {$summary['internal']} عملية داخلية و{$summary['external']} عملية بمركب خارجي إلى الحسابات.");
    }

    public function create()
    {
        return view('staff.operation-assignments.form', $this->formData(new OperationAssignment()));
    }

    public function store(Request $request)
    {
        $validated = $this->validateAssignment($request);
        $this->assertValidLinks($validated);
        OperationAssignment::create($validated);

        return redirect()->route('operation-assignments.index')->with('status', 'Assignment created successfully.');
    }

    public function edit(OperationAssignment $operationAssignment)
    {
        $this->assertAssignmentAccess($operationAssignment);

        return view('staff.operation-assignments.form', $this->formData($operationAssignment));
    }

    public function update(Request $request, OperationAssignment $operationAssignment)
    {
        $this->assertAssignmentAccess($operationAssignment);
        $validated = $this->validateAssignment($request, $operationAssignment);
        $this->assertValidLinks($validated, $operationAssignment);
        $operationAssignment->update($validated);

        return redirect()->route('operation-assignments.index')->with('status', 'Assignment updated successfully.');
    }

    public function destroy(OperationAssignment $operationAssignment)
    {
        $this->assertAssignmentAccess($operationAssignment);
        $operationId = $operationAssignment->operation_id;
        $this->assertOperationUnposted($operationId);
        $operationAssignment->delete();
        $operation = operations::find($operationId);
        if ($operation) {
            $operation->status = 'cancel';
            $operation->save();
        }

        return redirect()->route('operation-assignments.index')->with('status', 'Assignment deleted successfully.');
    }

    private function validateAssignment(Request $request, ?OperationAssignment $assignment = null): array
    {
        return $request->validate([
            'operation_id' => ['required', 'integer', 'tenant_exists:operations,id'],
            'type' => ['required', Rule::in(['boat', 'vehicle'])],
            'resource_id' => ['required', 'integer'],
            'driver_id' => ['nullable', 'integer'],
            'assign_date' => ['required', 'date'],
        ]);
    }

    private function assertValidLinks(array $data, ?OperationAssignment $assignment = null): void
    {
        $resourceModel = $data['type'] === 'boat' ? Boat::class : Vehicle::class;
        $operation = StaffRegionAccess::operationQuery(Auth::guard('staff')->user())
            ->join('trips', 'trips.id', '=', 'operations.id_trip')
            ->where('operations.id', $data['operation_id'])
            ->where('operations.status', 'complete')
            ->first(['operations.id', 'operations.accounting_entry_id', 'trips.region_id']);
        abort_unless($operation, 422, 'The selected operation is invalid.');
        $this->assertOperationUnposted((int) $operation->id);

        $resourceQuery = $resourceModel::whereKey($data['resource_id']);
        $resourceQuery->when($data['type'] === 'boat', fn ($query) => $query->where('is_active', true))
            ->when($data['type'] === 'vehicle', fn ($query) => $query->where('status', 'active'));
        $resourceQuery->where('region_id', $operation->region_id);
        abort_unless($resourceQuery->exists(), 422, 'The selected resource is invalid, inactive, or outside the operation region.');

        if (! empty($data['driver_id'])) {
            if ($data['type'] === 'boat') {
                $driverIsAttached = Boat_Driver::whereKey($data['driver_id'])
                    ->where('boat_id', $data['resource_id'])->exists();
            } else {
                $driverIsAttached = Drive::whereKey($data['driver_id'])->exists()
                    && \App\Support\TenantDatabase::table('vehicle_drivers')->where('vehicle_id', $data['resource_id'])
                        ->where('driver_id', $data['driver_id'])->exists();
            }
            abort_unless($driverIsAttached, 422, 'The selected driver is not assigned to this resource.');
        }

        $duplicate = OperationAssignment::where('operation_id', $data['operation_id'])
            ->where('type', $data['type'])->where('resource_id', $data['resource_id'])
            ->whereDate('assign_date', $data['assign_date'])
            ->when($assignment, fn ($q) => $q->where('id', '!=', $assignment->id))->exists();
        abort_if($duplicate, 422, 'This operation is already assigned to that resource on this date.');
    }

    private function formData(OperationAssignment $assignment): array
    {
        return [
            'assignment' => $assignment,
            'operations' => StaffRegionAccess::operationQuery(Auth::guard('staff')->user())
                ->where('status', 'complete')
                ->whereNull('accounting_entry_id')
                ->orderByDesc('trip_date')
                ->get(['id', 'booking_id', 'trip_date']),
            'boats' => StaffRegionAccess::boatQuery(Auth::guard('staff')->user())
                ->orderBy('name')->get(['id', 'name', 'code', 'region_id']),
            'vehicles' => StaffRegionAccess::vehicleQuery(Auth::guard('staff')->user())
                ->orderBy('name')->get(['id', 'name', 'code', 'region_id']),
            'drivers' => [
                'boat' => Boat_Driver::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'vehicle' => Drive::orderBy('name')->get(['id', 'name']),
            ],
            'resourceDrivers' => [
                'boat' => Boat_Driver::where('is_active', true)->get(['boat_id', 'id'])->groupBy('boat_id')->map(fn ($rows) => $rows->pluck('id')->values()),
                'vehicle' => \App\Support\TenantDatabase::table('vehicle_drivers')->get(['vehicle_id', 'driver_id'])->groupBy('vehicle_id')->map(fn ($rows) => $rows->pluck('driver_id')->values()),
            ],
        ];
    }

    private function assertAssignmentAccess(OperationAssignment $assignment): void
    {
        $accessible = StaffRegionAccess::operationQuery(Auth::guard('staff')->user())
            ->whereKey($assignment->operation_id)
            ->exists();
        abort_unless($accessible, 404);
    }

    private function accountId(string $preferredNumber, string $name, string $type): int
    {
        $account = \App\Support\TenantDatabase::table('accounts')->where('name', $name)->where('type', $type)->first();
        if ($account) {
            return (int) $account->id;
        }

        $accountNumber = (int) $preferredNumber;
        while (true) {
            if (intdiv($accountNumber, 100) !== 41) {
                throw ValidationException::withMessages([
                    'operation_ids' => "تعذر إنشاء حساب {$name} تلقائيًا؛ راجع دليل الحسابات.",
                ]);
            }
            if (! \App\Support\TenantDatabase::table('accounts')->where('acc_no', (string) $accountNumber)->exists()) {
                break;
            }
            $accountNumber++;
        }

        return (int) \App\Support\TenantDatabase::table('accounts')->insertGetId([
            'acc_no' => (string) $accountNumber,
            'name' => $name,
            'type' => $type,
            'opening_balance' => 0,
            'budget' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function splitAmountCents(int $amountInCents, int $recipientCount): array
    {
        if ($recipientCount < 1) {
            throw new \InvalidArgumentException('At least one boat is required to split revenue.');
        }

        $baseShare = intdiv($amountInCents, $recipientCount);
        $remainingCents = $amountInCents % $recipientCount;

        return array_map(
            fn (int $index): int => $baseShare + ($index < $remainingCents ? 1 : 0),
            range(0, $recipientCount - 1),
        );
    }

    private function shouldUseEmployeeCustody(bool $isExternal, ?string $employeeRole): bool
    {
        return ! $isExternal && $employeeRole === 'staff';
    }

    private function assertOperationUnposted(int $operationId): void
    {
        if (operations::whereKey($operationId)->whereNotNull('accounting_entry_id')->exists()) {
            throw ValidationException::withMessages([
                'operation_id' => 'لا يمكن تعديل أو حذف تعيينات عملية سبق ترحيلها إلى الحسابات.',
            ]);
        }
    }
}
