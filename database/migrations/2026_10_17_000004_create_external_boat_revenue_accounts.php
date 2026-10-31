<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $parent = DB::table('accounts')->where('acc_no', '4104')->first();
            if ($parent && ($parent->name !== 'إيرادات عمليات خارجية' || $parent->type !== 'إيرادات')) {
                throw new RuntimeException('رقم الحساب 4104 مستخدم لحساب مختلف.');
            }
            if (! $parent) {
                DB::table('accounts')->insert([
                    'acc_no' => '4104',
                    'name' => 'إيرادات عمليات خارجية',
                    'type' => 'إيرادات',
                    'opening_balance' => 0,
                    'budget' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach (DB::table('boats')->where('ownership_type', 'external')->orderBy('id')->get(['id', 'name']) as $boat) {
                $accountName = "إيرادات مركب - {$boat->name} (#{$boat->id})";
                $account = DB::table('accounts')->where('name', $accountName)->where('type', 'إيرادات')->first();
                if ($account) {
                    $accountNumber = (string) $account->acc_no;
                    if (! str_starts_with($accountNumber, '4104') || strlen($accountNumber) <= 4) {
                        DB::table('accounts')->where('id', $account->id)->update([
                            'acc_no' => $this->nextChildAccountNumber(),
                            'updated_at' => now(),
                        ]);
                    }

                    continue;
                }

                DB::table('accounts')->insert([
                    'acc_no' => $this->nextChildAccountNumber(),
                    'name' => $accountName,
                    'type' => 'إيرادات',
                    'opening_balance' => 0,
                    'budget' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
    }

    private function nextChildAccountNumber(): string
    {
        $usedNumbers = DB::table('accounts')
            ->where('acc_no', 'like', '4104%')
            ->pluck('acc_no')
            ->map(fn ($number) => (string) $number)
            ->all();

        foreach (range(1, 99) as $sequence) {
            $candidate = '4104'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            if (! in_array($candidate, $usedNumbers, true)) {
                return $candidate;
            }
        }

        throw new RuntimeException('لا توجد أرقام فرعية متاحة تحت حساب إيرادات العمليات الخارجية 4104.');
    }
};
