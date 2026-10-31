<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmployeeCustodyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeCustodyController extends Controller
{
    public function index(Request $request, EmployeeCustodyService $custody)
    {
        $data = $request->validate([
            'employee_id' => ['nullable', 'integer'],
        ]);

        $employees = \App\Support\TenantDatabase::table('users')
            ->leftJoin('staff_profiles', 'staff_profiles.user_id', '=', 'users.id')
            ->where('users.role', 'staff')
            ->select('users.id', 'users.name', 'users.username', 'staff_profiles.phone', 'staff_profiles.position')
            ->orderBy('users.name')
            ->get();

        $employee = null;
        $sales = collect();
        $deposits = collect();
        $salesTotal = 0.0;
        $collectedTotal = 0.0;
        $depositedTotal = 0.0;

        if (! empty($data['employee_id'])) {
            $employee = User::query()
                ->where('role', 'staff')
                ->findOrFail($data['employee_id']);
            $employeeProfile = \App\Support\TenantDatabase::table('staff_profiles')
                ->leftJoin('departments', 'departments.id', '=', 'staff_profiles.department_id')
                ->where('staff_profiles.user_id', $employee->id)
                ->first([
                    'staff_profiles.phone',
                    'staff_profiles.position',
                    'departments.name as department_name',
                ]);

            $sales = \App\Support\TenantDatabase::table('operations')
                ->leftJoin('customers', 'customers.id', '=', 'operations.customer_id')
                ->leftJoin('trips', 'trips.id', '=', 'operations.id_trip')
                ->where('operations.by_created', $employee->id)
                ->where('operations.status', 'complete')
                ->orderByDesc('operations.trip_date')
                ->orderByDesc('operations.id')
                ->get([
                    'operations.id',
                    'operations.booking_id',
                    'customers.name as customer_name',
                    'trips.name as trip_name',
                    'operations.trip_date',
                    'operations.trip_time',
                    'operations.totalamount',
                    'operations.amount_total',
                    'operations.amount_paid',
                    'operations.status_payment',
                    'operations.accounting_entry_id',
                ]);

            foreach ($sales as $sale) {
                $sale->totalamount = round((float) $sale->totalamount, 2);
                $sale->collected_amount = $custody->collectedAmount(
                    $sale->totalamount,
                    (float) $sale->amount_total,
                    (float) $sale->amount_paid,
                );
                $salesTotal += $sale->totalamount;
                $collectedTotal += $sale->collected_amount;
            }

            $deposits = \App\Support\TenantDatabase::table('employee_custody_deposits as deposits')
                ->leftJoin('users as receivers', 'receivers.id', '=', 'deposits.received_by')
                ->where('deposits.employee_id', $employee->id)
                ->orderByDesc('deposits.deposit_date')
                ->orderByDesc('deposits.id')
                ->get([
                    'deposits.id',
                    'deposits.amount',
                    'deposits.deposit_date',
                    'deposits.region',
                    'deposits.notes',
                    'receivers.name as receiver_name',
                ]);
            $depositedTotal = (float) $deposits->sum('amount');
        }

        return view('admin.pages.employee_custody', [
            'user' => auth()->user(),
            'roleName' => [
                'admin' => 'مدير النظام',
                'accountant' => 'محاسب',
            ][auth()->user()->role] ?? '',
            'employees' => $employees,
            'employee' => $employee,
            'employeeProfile' => $employeeProfile ?? null,
            'sales' => $sales,
            'deposits' => $deposits,
            'salesTotal' => round($salesTotal, 2),
            'collectedTotal' => round($collectedTotal, 2),
            'depositedTotal' => round($depositedTotal, 2),
            'custodyBalance' => round($collectedTotal - $depositedTotal, 2),
            'regions' => \App\Support\TenantDatabase::table('regions')->where('is_active', true)->orderBy('name')->pluck('name'),
            'selectedEmployeeId' => $employee?->id,
        ]);
    }

    public function deposit(Request $request, EmployeeCustodyService $custody)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'tenant_exists:users,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'deposit_date' => ['required', 'date'],
            'region' => ['required', 'string', 'tenant_exists:regions,name'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $custody): void {
            $employee = User::query()
                ->where('role', 'staff')
                ->whereKey($data['employee_id'])
                ->lockForUpdate()
                ->first();

            if (! $employee) {
                throw ValidationException::withMessages([
                    'employee_id' => 'الموظف المحدد غير موجود.',
                ]);
            }

            $region = \App\Support\TenantDatabase::table('regions')
                ->where('name', $data['region'])
                ->where('is_active', true)
                ->first();
            if (! $region) {
                throw ValidationException::withMessages([
                    'region' => 'المنطقة المحددة غير مفعّلة.',
                ]);
            }

            $sales = \App\Support\TenantDatabase::table('operations')
                ->where('by_created', $employee->id)
                ->where('status', 'complete')
                ->get(['totalamount', 'amount_total', 'amount_paid']);

            $collectedTotal = $sales->sum(fn ($sale) => $custody->collectedAmount(
                (float) $sale->totalamount,
                (float) $sale->amount_total,
                (float) $sale->amount_paid,
            ));
            $depositedTotal = (float) \App\Support\TenantDatabase::table('employee_custody_deposits')
                ->where('employee_id', $employee->id)
                ->sum('amount');
            $balance = round($collectedTotal - $depositedTotal, 2);
            $amount = round((float) $data['amount'], 2);

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'المبلغ أكبر من رصيد عهدة الموظف المتاح ('.number_format(max($balance, 0), 2).' جنيه).',
                ]);
            }

            $depositId = \App\Support\TenantDatabase::table('employee_custody_deposits')->insertGetId([
                'employee_id' => $employee->id,
                'received_by' => auth()->id(),
                'amount' => $amount,
                'deposit_date' => $data['deposit_date'],
                'region' => $region->name,
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $now = now();
            $entryId = \App\Support\TenantDatabase::table('journal_entries')->insertGetId([
                'movement_no' => 'CUSTODY-'.$depositId,
                'entry_date' => $data['deposit_date'],
                'description' => "توريد عهدة الموظف {$employee->name} للصندوق",
                'region' => $region->name,
                'status' => 'posted',
                'entry_type' => 'تسوية',
                'created_by' => auth()->id(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            \App\Support\TenantDatabase::table('journal_lines')->insert([
                [
                    'entry_id' => $entryId,
                    'account_id' => $custody->cashAccountId(),
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'entry_id' => $entryId,
                    'account_id' => $custody->employeeAccountId($employee->id, $employee->name),
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ]);

            \App\Support\TenantDatabase::table('employee_custody_deposits')->where('id', $depositId)->update([
                'journal_entry_id' => $entryId,
                'updated_at' => $now,
            ]);
            \App\Support\TenantDatabase::table('audit_log')->insert([
                'user' => auth()->user()->name,
                'action' => 'توريد عهدة موظف للصندوق',
                'details' => "{$employee->name} — {$amount} — CUSTODY-{$depositId}",
                'at' => $now,
            ]);
        });

        return redirect()
            ->route('employee-custody.index', ['employee_id' => $data['employee_id']])
            ->with('status', 'تم استلام المبلغ وتسجيله في الصندوق.');
    }
}
