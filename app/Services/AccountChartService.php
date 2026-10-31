<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AccountChartService
{
    public function provisionTenant(int $tenantId): void
    {
        foreach (DB::table('account_templates')->orderBy('acc_no')->get() as $template) {
            $query = DB::table('accounts')
                ->where('tenant_id', $tenantId)
                ->where('acc_no', $template->acc_no);
            if ($query->exists()) {
                $query->update([
                    'name' => $template->name,
                    'type' => $template->type,
                    'is_system' => true,
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('accounts')->insert([
                'tenant_id' => $tenantId,
                'acc_no' => $template->acc_no,
                'name' => $template->name,
                'type' => $template->type,
                'is_system' => true,
                'opening_balance' => 0,
                'budget' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
