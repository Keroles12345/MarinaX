<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeCustodyService
{
    public function employeeAccountId(int $userId, string $employeeName): int
    {
        $accountNumber = 'EMP-'.$userId;
        $account = \App\Support\TenantDatabase::table('accounts')->where('acc_no', $accountNumber)->first();
        $name = "عهدة الموظف: {$employeeName} (#{$userId})";

        if ($account) {
            if ($account->type !== 'أصول') {
                throw ValidationException::withMessages([
                    'employee_id' => "رقم الحساب {$accountNumber} مستخدم لحساب غير صالح للعهدة.",
                ]);
            }

            if ($account->name !== $name) {
                \App\Support\TenantDatabase::table('accounts')->where('id', $account->id)->update([
                    'name' => $name,
                    'updated_at' => now(),
                ]);
            }

            return (int) $account->id;
        }

        return (int) \App\Support\TenantDatabase::table('accounts')->insertGetId([
            'acc_no' => $accountNumber,
            'name' => $name,
            'type' => 'أصول',
            'opening_balance' => 0,
            'budget' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function cashAccountId(): int
    {
        $account = \App\Support\TenantDatabase::table('accounts')->where('name', 'الصندوق')->where('type', 'أصول')->first();
        if ($account) {
            return (int) $account->id;
        }

        $accountNumber = 1101;
        while (\App\Support\TenantDatabase::table('accounts')->where('acc_no', (string) $accountNumber)->exists()) {
            $accountNumber++;
            if ($accountNumber > 1199) {
                throw ValidationException::withMessages([
                    'amount' => 'تعذر إنشاء حساب الصندوق تلقائيًا؛ راجع دليل الحسابات.',
                ]);
            }
        }

        return (int) \App\Support\TenantDatabase::table('accounts')->insertGetId([
            'acc_no' => (string) $accountNumber,
            'name' => 'الصندوق',
            'type' => 'أصول',
            'opening_balance' => 0,
            'budget' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function collectedAmount(float $totalAmountEgp, float $operationTotal, float $amountPaid): float
    {
        $paidRatio = $operationTotal > 0
            ? min(max($amountPaid / $operationTotal, 0), 1)
            : 0;

        return round($totalAmountEgp * $paidRatio, 2);
    }
}
