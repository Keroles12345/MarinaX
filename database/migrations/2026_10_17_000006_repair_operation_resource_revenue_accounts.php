<?php

use App\Services\OperationRevenueAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $boats = DB::table('boats as b')
                ->join('vehicles as v', 'v.id', '=', 'b.id')
                ->whereColumn('b.revenue_account_id', 'v.revenue_account_id')
                ->join('accounts as a', 'a.id', '=', 'b.revenue_account_id')
                ->where('a.name', 'like', 'إيرادات سيارة - %')
                ->select('b.id', 'b.name', 'b.revenue_account_id', 'a.acc_no')
                ->get();

            foreach ($boats as $boat) {
                $hasBoatRevenueHistory = DB::table('journal_lines as jl')
                    ->join('journal_entries as je', 'je.id', '=', 'jl.entry_id')
                    ->join('operations as o', 'o.accounting_entry_id', '=', 'je.id')
                    ->join('operation_assignments as oa', function ($join) use ($boat): void {
                        $join->on('oa.operation_id', '=', 'o.id')
                            ->where('oa.type', '=', 'boat')
                            ->where('oa.resource_id', '=', $boat->id);
                    })
                    ->where('jl.account_id', $boat->revenue_account_id)
                    ->where('jl.credit', '>', 0)
                    ->exists();

                if ($hasBoatRevenueHistory) {
                    DB::table('accounts')->where('id', $boat->revenue_account_id)->update([
                        'name' => "إيرادات مركب - {$boat->name} (#{$boat->id})",
                        'updated_at' => now(),
                    ]);
                    DB::table('boats')->where('id', $boat->id)->update([
                        'revenue_account_id' => $boat->revenue_account_id,
                    ]);
                }
            }

            $revenueAccounts = app(OperationRevenueAccountService::class);
            foreach (DB::table('boats')->orderBy('id')->get(['id', 'name', 'ownership_type']) as $boat) {
                $revenueAccounts->ensure('boat', (int) $boat->id, $boat->name, $boat->ownership_type);
            }
            foreach (DB::table('vehicles')->orderBy('id')->get(['id', 'name', 'ownership_type']) as $vehicle) {
                $revenueAccounts->ensure('vehicle', (int) $vehicle->id, $vehicle->name, $vehicle->ownership_type);
            }
        });
    }

    public function down(): void
    {
    }
};
