<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperationRevenueAccountService
{
    public function ensure(string $resourceType, int $resourceId, string $resourceName, string $ownershipType): int
    {
        if (! in_array($resourceType, ['boat', 'vehicle'], true)) {
            throw new \InvalidArgumentException('Unsupported operation resource type.');
        }
        if (! in_array($ownershipType, ['internal', 'external'], true)) {
            throw new \InvalidArgumentException('Unsupported operation resource ownership type.');
        }

        $parentCode = $ownershipType === 'external' ? '4104' : '4103';
        $parentName = $ownershipType === 'external' ? 'إيرادات عمليات خارجية' : 'إيرادات عمليات داخلية';
        $resourceLabel = match ([$resourceType, $ownershipType]) {
            ['boat', 'external'] => 'مركب خارجي',
            ['boat', 'internal'] => 'مركب',
            ['vehicle', 'external'] => 'سيارة خارجية',
            ['vehicle', 'internal'] => 'سيارة',
        };
        $accountName = "إيرادات {$resourceLabel} - {$resourceName} (#{$resourceId})";
        $this->ensureParent($parentCode, $parentName);

        $resourceTable = $resourceType === 'boat' ? 'boats' : 'vehicles';
        $resource = \App\Support\TenantDatabase::table($resourceTable)->where('id', $resourceId)->first();
        if (! $resource) {
            throw new \RuntimeException("The {$resourceType} resource no longer exists.");
        }

        $account = ! empty($resource->revenue_account_id)
            ? \App\Support\TenantDatabase::table('accounts')->where('id', $resource->revenue_account_id)->first()
            : null;
        if ($account && ! $this->isResourceAccount($account->name, $resourceType, $resourceId)) {
            $account = null;
        }
        if (! $account) {
            $account = \App\Support\TenantDatabase::table('accounts')
                ->where('type', 'إيرادات')
                ->where('name', 'like', "% (#{$resourceId})")
                ->where(function ($query) use ($resourceType): void {
                    if ($resourceType === 'boat') {
                        $query->where('name', 'like', 'إيرادات مركب - %')
                            ->orWhere('name', 'like', 'إيرادات مركب خارجي - %')
                            ->orWhere('name', 'like', 'إيرادات تشغيل داخلي - %');
                    } else {
                        $query->where('name', 'like', 'إيرادات سيارة - %');
                        $query->orWhere('name', 'like', 'إيرادات سيارة خارجية - %');
                    }
                })
                ->first();
        }

        if ($account) {
            $accountNumber = (string) $account->acc_no;
            $updates = ['name' => $accountName, 'type' => 'إيرادات'];
            if (! str_starts_with($accountNumber, $parentCode) || strlen($accountNumber) <= strlen($parentCode)) {
                $updates['acc_no'] = $this->nextChildAccountNumber($parentCode);
            }
            if ($account->name !== $accountName || $account->type !== 'إيرادات' || isset($updates['acc_no'])) {
                $updates['updated_at'] = now();
                \App\Support\TenantDatabase::table('accounts')->where('id', $account->id)->update($updates);
            }
            $accountId = (int) $account->id;
        } else {
            $accountId = (int) \App\Support\TenantDatabase::table('accounts')->insertGetId([
                'acc_no' => $this->nextChildAccountNumber($parentCode),
                'name' => $accountName,
                'type' => 'إيرادات',
                'opening_balance' => 0,
                'budget' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \App\Support\TenantDatabase::table($resourceTable)->where('id', $resourceId)->update([
            'revenue_account_id' => $accountId,
        ]);

        return $accountId;
    }

    private function isResourceAccount(string $accountName, string $resourceType, int $resourceId): bool
    {
        $prefix = $resourceType === 'boat'
            ? ['إيرادات مركب - ', 'إيرادات مركب خارجي - ', 'إيرادات تشغيل داخلي - ']
            : ['إيرادات سيارة - ', 'إيرادات سيارة خارجية - '];

        foreach ($prefix as $start) {
            if (str_starts_with($accountName, $start) && str_ends_with($accountName, "(#{$resourceId})")) {
                return true;
            }
        }

        return false;
    }

    private function ensureParent(string $accountNumber, string $name): void
    {
        $parent = \App\Support\TenantDatabase::table('accounts')->where('acc_no', $accountNumber)->first();
        if ($parent) {
            if ($parent->name !== $name || $parent->type !== 'إيرادات') {
                throw ValidationException::withMessages([
                    'resource' => "رقم الحساب {$accountNumber} مستخدم لحساب آخر. صحح دليل الحسابات قبل إضافة المورد.",
                ]);
            }

            return;
        }

        \App\Support\TenantDatabase::table('accounts')->insert([
            'acc_no' => $accountNumber,
            'name' => $name,
            'type' => 'إيرادات',
            'opening_balance' => 0,
            'budget' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nextChildAccountNumber(string $parentCode): string
    {
        $usedNumbers = \App\Support\TenantDatabase::table('accounts')
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

        throw ValidationException::withMessages([
            'resource' => "لا توجد أرقام فرعية متاحة تحت حساب الإيرادات {$parentCode}.",
        ]);
    }
}
