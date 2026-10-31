<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccountingTestDataSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartaccounting_test') {
            throw new RuntimeException('Accounting test data may only be added to the smartaccounting_test database.');
        }

        DB::transaction(function (): void {
            $now = now();
            $regionName = 'منطقة اختبار الحسابات';
            $regionId = DB::table('regions')->where('name', $regionName)->value('id');

            if (! $regionId) {
                $regionId = DB::table('regions')->insertGetId([
                    'name' => $regionName,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $accounts = [
                '990101' => ['name' => 'بنك تجريبي', 'type' => 'أصول', 'budget' => 0],
                '990301' => ['name' => 'رأس مال تجريبي', 'type' => 'حقوق ملكية', 'budget' => 0],
                '990401' => ['name' => 'إيرادات تجريبية', 'type' => 'إيرادات', 'budget' => 5000],
                '990501' => ['name' => 'مصروفات تجريبية', 'type' => 'مصروفات', 'budget' => 1000],
            ];
            $accountIds = [];

            foreach ($accounts as $number => $account) {
                $existing = DB::table('accounts')->where('acc_no', $number)->first();
                if ($existing) {
                    $accountIds[$number] = $existing->id;

                    continue;
                }

                $accountIds[$number] = DB::table('accounts')->insertGetId([
                    'acc_no' => $number,
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'opening_balance' => 0,
                    'budget' => $account['budget'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $entries = [
                'TEST-20261017-001' => [
                    'description' => 'إيداع رأس مال تجريبي',
                    'lines' => [
                        ['990101', 10000, 0],
                        ['990301', 0, 10000],
                    ],
                ],
                'TEST-20261017-002' => [
                    'description' => 'تحصيل إيراد تجريبي',
                    'lines' => [
                        ['990101', 3000, 0],
                        ['990401', 0, 3000],
                    ],
                ],
                'TEST-20261017-003' => [
                    'description' => 'سداد مصروف تجريبي',
                    'lines' => [
                        ['990501', 700, 0],
                        ['990101', 0, 700],
                    ],
                ],
            ];

            foreach ($entries as $movementNo => $entry) {
                if (DB::table('journal_entries')->where('movement_no', $movementNo)->exists()) {
                    continue;
                }

                $entryId = DB::table('journal_entries')->insertGetId([
                    'movement_no' => $movementNo,
                    'entry_date' => '2026-10-17',
                    'description' => $entry['description'],
                    'region' => $regionName,
                    'status' => 'posted',
                    'entry_type' => 'عادي',
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($entry['lines'] as [$accountNo, $debit, $credit]) {
                    DB::table('journal_lines')->insert([
                        'entry_id' => $entryId,
                        'account_id' => $accountIds[$accountNo],
                        'debit' => $debit,
                        'credit' => $credit,
                    ]);
                }
            }
        });
    }
}
