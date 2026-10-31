<?php

use App\Services\OperationRevenueAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $parentAccounts = DB::table('accounts')
                ->whereIn('acc_no', ['4103', '4104'])
                ->pluck('id', 'acc_no');
            if ($parentAccounts->isEmpty()) {
                return;
            }

            $operations = DB::table('operations as o')
                ->join('journal_entries as je', 'je.id', '=', 'o.accounting_entry_id')
                ->whereNotNull('o.accounting_entry_id')
                ->where('je.status', 'posted')
                ->select('o.id', 'o.accounting_entry_id', 'je.movement_no')
                ->orderBy('o.id')
                ->get();
            $revenueAccounts = app(OperationRevenueAccountService::class);

            foreach ($operations as $operation) {
                $parentLines = DB::table('journal_lines')
                    ->where('entry_id', $operation->accounting_entry_id)
                    ->whereIn('account_id', $parentAccounts->values())
                    ->get();
                if ($parentLines->isEmpty()) {
                    continue;
                }

                $assignments = DB::table('operation_assignments')
                    ->where('operation_id', $operation->id)
                    ->select('type', 'resource_id')
                    ->distinct()
                    ->orderBy('type')
                    ->orderBy('resource_id')
                    ->get();
                if ($assignments->isEmpty()) {
                    continue;
                }

                $alreadyClassified = DB::table('journal_lines as jl')
                    ->join('accounts as a', 'a.id', '=', 'jl.account_id')
                    ->where('jl.entry_id', $operation->accounting_entry_id)
                    ->where(function ($query): void {
                        $query->where(function ($child): void {
                            $child->where('a.acc_no', 'like', '4103%')
                                ->where('a.acc_no', '!=', '4103');
                        })->orWhere(function ($child): void {
                            $child->where('a.acc_no', 'like', '4104%')
                                ->where('a.acc_no', '!=', '4104');
                        });
                    })
                    ->exists();
                if ($alreadyClassified) {
                    throw new RuntimeException(
                        "Cannot safely classify {$operation->movement_no}: it already has resource revenue lines."
                    );
                }

                $resourceAccountIds = [];
                foreach ($assignments as $assignment) {
                    if (! in_array($assignment->type, ['boat', 'vehicle'], true)) {
                        throw new RuntimeException("Unsupported resource type in {$operation->movement_no}.");
                    }

                    $table = $assignment->type === 'boat' ? 'boats' : 'vehicles';
                    $resource = DB::table($table)->where('id', $assignment->resource_id)->first();
                    if (! $resource) {
                        throw new RuntimeException(
                            "Cannot safely classify {$operation->movement_no}: assigned resource is missing."
                        );
                    }

                    $resourceAccountIds[] = $revenueAccounts->ensure(
                        $assignment->type,
                        (int) $assignment->resource_id,
                        $resource->name,
                        $resource->ownership_type,
                    );
                }

                $resourceAccountIds = array_values(array_unique($resourceAccountIds));
                $debitCents = (int) round($parentLines->sum(fn ($line) => (float) $line->debit) * 100);
                $creditCents = (int) round($parentLines->sum(fn ($line) => (float) $line->credit) * 100);
                $debitShares = $this->splitCents($debitCents, count($resourceAccountIds));
                $creditShares = $this->splitCents($creditCents, count($resourceAccountIds));

                DB::table('journal_lines')
                    ->whereIn('id', $parentLines->pluck('id'))
                    ->delete();

                foreach ($resourceAccountIds as $index => $accountId) {
                    if ($debitShares[$index] === 0 && $creditShares[$index] === 0) {
                        continue;
                    }

                    DB::table('journal_lines')->insert([
                        'entry_id' => $operation->accounting_entry_id,
                        'account_id' => $accountId,
                        'debit' => $debitShares[$index] / 100,
                        'credit' => $creditShares[$index] / 100,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
    }

    private function splitCents(int $amount, int $recipientCount): array
    {
        if ($recipientCount === 0) {
            throw new RuntimeException('Cannot split operation revenue without an assigned resource.');
        }

        $share = intdiv($amount, $recipientCount);
        $remainder = $amount % $recipientCount;

        return array_map(
            fn (int $index): int => $share + ($index < $remainder ? 1 : 0),
            range(0, $recipientCount - 1),
        );
    }
};
