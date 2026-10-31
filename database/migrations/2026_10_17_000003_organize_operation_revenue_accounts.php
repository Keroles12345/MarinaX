<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $parents = [
                '4103' => 'إيرادات عمليات داخلية',
                '4104' => 'إيرادات عمليات خارجية',
            ];

            foreach ($parents as $accountNumber => $name) {
                $account = DB::table('accounts')->where('acc_no', $accountNumber)->first();
                if ($account && ($account->name !== $name || $account->type !== 'إيرادات')) {
                    throw new RuntimeException("رقم الحساب {$accountNumber} مستخدم لحساب مختلف.");
                }
                if (! $account) {
                    DB::table('accounts')->insert([
                        'acc_no' => $accountNumber,
                        'name' => $name,
                        'type' => 'إيرادات',
                        'opening_balance' => 0,
                        'budget' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $legacyAccounts = DB::table('accounts')
                ->where('type', 'إيرادات')
                ->where(function ($query): void {
                    $query->where('name', 'like', 'إيرادات مركب خارجي - %')
                        ->orWhere('name', 'like', 'إيرادات تشغيل داخلي - %');
                })
                ->get();

            foreach ($legacyAccounts as $account) {
                if (! preg_match('/^(إيرادات مركب خارجي|إيرادات تشغيل داخلي) - (.+) \(#(\d+)\)$/u', $account->name, $matches)) {
                    continue;
                }

                $parentCode = $matches[1] === 'إيرادات مركب خارجي' ? '4104' : '4103';
                $canonicalName = "إيرادات مركب - {$matches[2]} (#{$matches[3]})";
                $existing = DB::table('accounts')
                    ->where('type', 'إيرادات')
                    ->where('name', $canonicalName)
                    ->where('id', '!=', $account->id)
                    ->first();
                if ($existing) {
                    DB::table('journal_lines')
                        ->where('account_id', $account->id)
                        ->update(['account_id' => $existing->id]);
                    DB::table('accounts')->where('id', $existing->id)->update([
                        'opening_balance' => (float) $existing->opening_balance + (float) $account->opening_balance,
                        'budget' => (float) $existing->budget + (float) $account->budget,
                        'updated_at' => now(),
                    ]);
                    DB::table('accounts')->where('id', $account->id)->delete();

                    continue;
                }

                $accountNumber = (string) $account->acc_no;
                if (! str_starts_with($accountNumber, $parentCode)
                    || strlen($accountNumber) <= strlen($parentCode)) {
                    $accountNumber = $this->nextChildAccountNumber($parentCode);
                    DB::table('accounts')->where('id', $account->id)->update([
                        'acc_no' => $accountNumber,
                        'name' => $canonicalName,
                        'updated_at' => now(),
                    ]);
                } elseif ($account->name !== $canonicalName) {
                    DB::table('accounts')->where('id', $account->id)->update([
                        'name' => $canonicalName,
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
    }

    private function nextChildAccountNumber(string $parentCode): string
    {
        $usedNumbers = DB::table('accounts')
            ->where('acc_no', 'like', $parentCode.'%')
            ->pluck('acc_no')
            ->map(fn ($number) => (string) $number)
            ->all();

        foreach (range(1, 99) as $sequence) {
            $candidate = $parentCode.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            if (! in_array($candidate, $usedNumbers, true)) {
                return $candidate;
            }
        }

        throw new RuntimeException("لا توجد أرقام فرعية متاحة تحت حساب الإيرادات {$parentCode}.");
    }
};
