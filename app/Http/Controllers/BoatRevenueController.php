<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoatRevenueController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'ownership_type' => ['nullable', 'in:internal,external'],
            'boat_id' => ['nullable', 'integer', 'tenant_exists:boats,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $boatOptions = Boat::query()
            ->leftJoin('accounts', 'accounts.id', '=', 'boats.revenue_account_id')
            ->select('boats.id', 'boats.name', 'boats.code', 'boats.ownership_type', 'boats.is_active', 'boats.revenue_account_id', 'accounts.acc_no')
            ->orderBy('boats.name')
            ->get();
        $boats = $boatOptions
            ->when(
                filled($filters['ownership_type'] ?? null),
                fn ($boats) => $boats->where('ownership_type', $filters['ownership_type'])
            )
            ->values();

        $summaryQuery = \App\Support\TenantDatabase::table('boats')
            ->leftJoin('journal_lines', 'journal_lines.account_id', '=', 'boats.revenue_account_id')
            ->leftJoin('journal_entries', function ($join) use ($filters): void {
                $join->on('journal_entries.id', '=', 'journal_lines.entry_id')
                    ->where('journal_entries.status', 'posted')
                    ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('journal_entries.entry_date', '>=', $from))
                    ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('journal_entries.entry_date', '<=', $to));
            })
            ->select(
                'boats.id',
                DB::raw('COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.credit - journal_lines.debit ELSE 0 END), 0) as revenue_total'),
            )
            ->groupBy('boats.id');
        $revenueByBoat = $summaryQuery->pluck('revenue_total', 'id');
        $boats->each(fn ($boat) => $boat->revenue_total = round((float) $revenueByBoat->get($boat->id, 0), 2));

        $selectedBoat = null;
        $entries = collect();
        $revenueTotal = 0.0;

        if (! empty($filters['boat_id'])) {
            $selectedBoat = $boats->firstWhere('id', (int) $filters['boat_id']);

            if ($selectedBoat && $selectedBoat->revenue_account_id) {
                $entriesQuery = \App\Support\TenantDatabase::table('journal_lines as lines')
                ->join('journal_entries as entries', 'entries.id', '=', 'lines.entry_id')
                ->leftJoin('operations', 'operations.accounting_entry_id', '=', 'entries.id')
                ->where('lines.account_id', $selectedBoat->revenue_account_id)
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
                $entries = $entriesQuery->paginate(10)->withQueryString();
                if ($request->expectsJson()) {
                    return response()->json([
                        'html' => view('admin.pages.partials.boat-revenue-rows', compact('entries'))->render(),
                        'nextPageUrl' => $entries->nextPageUrl(),
                        'hasMore' => $entries->hasMorePages(),
                        'summary' => __('عرض :from إلى :to من أصل :total سجل', [
                            'from' => number_format($entries->firstItem()),
                            'to' => number_format($entries->lastItem()),
                            'total' => number_format($entries->total()),
                        ]),
                    ]);
                }
            }
        }

        $periodTotal = round((float) $boats->sum('revenue_total'), 2);

        return view('admin.pages.boat_revenues', [
            'user' => auth()->user(),
            'roleName' => [
                'admin' => 'مدير النظام',
                'accountant' => 'محاسب',
                'viewer' => 'مشاهدة فقط',
                'account_user' => 'موظف حسابات',
            ][auth()->user()->role] ?? '',
            'boats' => $boats,
            'boatOptions' => $boatOptions,
            'selectedBoat' => $selectedBoat,
            'entries' => $entries,
            'revenueTotal' => $revenueTotal,
            'periodTotal' => $periodTotal,
            'filters' => $filters,
        ]);
    }
}
