<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sourceTenant = DB::table('tenants')->where('name', 'الشركة الرئيسية')->first();
        $sourceTenantId = $sourceTenant?->id
            ?? DB::table('accounts')->orderBy('tenant_id')->value('tenant_id');

        if (! $sourceTenantId) {
            return;
        }

        $companySpecificAccounts = function ($query): void {
            $query->where(function ($codes) {
                $codes->where(function ($internal) {
                    $internal->where('acc_no', 'like', '4103%')->where('acc_no', '<>', '4103');
                })->orWhere(function ($external) {
                    $external->where('acc_no', 'like', '4104%')->where('acc_no', '<>', '4104');
                })->orWhere('acc_no', 'like', 'EMP-%');
            });
        };

        DB::table('account_templates')->where($companySpecificAccounts)->delete();
        DB::table('accounts')->where($companySpecificAccounts)->update(['is_system' => false]);

        DB::table('accounts')
            ->where('tenant_id', '<>', $sourceTenantId)
            ->where($companySpecificAccounts)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('journal_lines')
                    ->whereColumn('journal_lines.account_id', 'accounts.id')
                    ->whereColumn('journal_lines.tenant_id', 'accounts.tenant_id');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('boats')
                    ->whereColumn('boats.revenue_account_id', 'accounts.id')
                    ->whereColumn('boats.tenant_id', 'accounts.tenant_id');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('vehicles')
                    ->whereColumn('vehicles.revenue_account_id', 'accounts.id')
                    ->whereColumn('vehicles.tenant_id', 'accounts.tenant_id');
            })
            ->delete();
    }

    public function down(): void
    {
        // Company-specific account rows must not be re-added to the shared chart automatically.
    }
};
