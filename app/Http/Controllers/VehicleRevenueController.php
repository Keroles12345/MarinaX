<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleRevenueController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'ownership_type' => ['nullable', 'in:internal,external'],
            'vehicle_id' => ['nullable', 'integer', 'tenant_exists:vehicles,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $vehicleOptions = Vehicle::query()
            ->leftJoin('vehicle_types', 'vehicle_types.id', '=', 'vehicles.vehicle_type_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'vehicles.revenue_account_id')
            ->select(
                'vehicles.id',
                'vehicles.name',
                'vehicles.code',
                'vehicles.status',
                'vehicles.ownership_type',
                'vehicles.vehicle_type_id',
                'vehicles.revenue_account_id',
                'vehicle_types.name as vehicle_type_name',
                'accounts.acc_no',
            )
            ->orderBy('vehicles.name')
            ->get();
        $vehicles = $vehicleOptions
            ->when(
                filled($filters['ownership_type'] ?? null),
                fn ($vehicles) => $vehicles->where('ownership_type', $filters['ownership_type'])
            )
            ->values();

        $revenueByVehicle = \App\Support\TenantDatabase::table('vehicles')
            ->leftJoin('journal_lines', 'journal_lines.account_id', '=', 'vehicles.revenue_account_id')
            ->leftJoin('journal_entries', function ($join) use ($filters): void {
                $join->on('journal_entries.id', '=', 'journal_lines.entry_id')
                    ->where('journal_entries.status', 'posted')
                    ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('journal_entries.entry_date', '>=', $from))
                    ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('journal_entries.entry_date', '<=', $to));
            })
            ->select(
                'vehicles.id as vehicle_id',
                DB::raw('COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.credit - journal_lines.debit ELSE 0 END), 0) as revenue_total'),
            )
            ->groupBy('vehicles.id')
            ->pluck('revenue_total', 'vehicle_id');
        $vehicles->each(fn ($vehicle) => $vehicle->revenue_total = round((float) $revenueByVehicle->get($vehicle->id, 0), 2));

        $selectedVehicle = null;
        $entries = collect();
        $revenueTotal = 0.0;

        if (! empty($filters['vehicle_id'])) {
            $selectedVehicle = $vehicles->firstWhere('id', (int) $filters['vehicle_id']);

            if ($selectedVehicle && $selectedVehicle->revenue_account_id) {
                $entriesQuery = \App\Support\TenantDatabase::table('journal_lines as lines')
                    ->join('journal_entries as entries', 'entries.id', '=', 'lines.entry_id')
                    ->leftJoin('operations', 'operations.accounting_entry_id', '=', 'entries.id')
                    ->where('lines.account_id', $selectedVehicle->revenue_account_id)
                    ->where('entries.status', 'posted')
                    ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('entries.entry_date', '>=', $from))
                    ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('entries.entry_date', '<=', $to))
                    ->select(
                        'entries.id as entry_id',
                        'entries.entry_date',
                        'entries.movement_no',
                        'entries.description',
                        'operations.booking_id',
                        DB::raw('lines.credit - lines.debit as revenue_amount'),
                    )
                    ->orderByDesc('entries.entry_date')
                    ->orderByDesc('entries.id');

                $revenueTotal = round((float) (clone $entriesQuery)->sum(DB::raw('lines.credit - lines.debit')), 2);
                $entries = $entriesQuery->paginate(30)->withQueryString();
            }
        }

        return view('admin.pages.vehicle_revenues', [
            'user' => auth()->user(),
            'roleName' => [
                'admin' => 'مدير النظام',
                'accountant' => 'محاسب',
                'viewer' => 'مشاهدة فقط',
                'account_user' => 'موظف حسابات',
            ][auth()->user()->role] ?? '',
            'vehicles' => $vehicles,
            'vehicleOptions' => $vehicleOptions,
            'selectedVehicle' => $selectedVehicle,
            'entries' => $entries,
            'revenueTotal' => $revenueTotal,
            'periodTotal' => round((float) $vehicles->sum('revenue_total'), 2),
            'filters' => $filters,
        ]);
    }
}
