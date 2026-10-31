<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_system')->default(false);
        });

        Schema::create('account_templates', function (Blueprint $table) {
            $table->id();
            $table->string('acc_no')->unique();
            $table->string('name');
            $table->string('type')->default('');
            $table->timestamps();
        });

        $sourceTenant = DB::table('tenants')->where('name', 'الشركة الرئيسية')->first();
        if (! $sourceTenant || ! DB::table('accounts')->where('tenant_id', $sourceTenant->id)->exists()) {
            $sourceTenantId = DB::table('accounts')->orderBy('tenant_id')->value('tenant_id');
        } else {
            $sourceTenantId = $sourceTenant->id;
        }

        if (! $sourceTenantId) {
            return;
        }

        $sourceAccounts = DB::table('accounts')
            ->where('tenant_id', $sourceTenantId)
            ->where(function ($query) {
                $query->whereNotLike('acc_no', '4103%')
                    ->orWhere('acc_no', '4103');
            })
            ->where(function ($query) {
                $query->whereNotLike('acc_no', '4104%')
                    ->orWhere('acc_no', '4104');
            })
            ->whereNotLike('acc_no', 'EMP-%')
            ->orderBy('acc_no')
            ->get(['acc_no', 'name', 'type']);
        $tenantIds = DB::table('tenants')->pluck('id');

        foreach ($sourceAccounts as $account) {
            DB::table('account_templates')->updateOrInsert(
                ['acc_no' => $account->acc_no],
                [
                    'name' => $account->name,
                    'type' => $account->type ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('accounts')
                ->where('acc_no', $account->acc_no)
                ->update(['is_system' => true]);

            foreach ($tenantIds as $tenantId) {
                $exists = DB::table('accounts')
                    ->where('tenant_id', $tenantId)
                    ->where('acc_no', $account->acc_no)
                    ->exists();

                if ($exists) {
                    DB::table('accounts')
                        ->where('tenant_id', $tenantId)
                        ->where('acc_no', $account->acc_no)
                        ->update([
                            'name' => $account->name,
                            'type' => $account->type ?? '',
                            'is_system' => true,
                            'updated_at' => now(),
                        ]);

                    continue;
                }

                DB::table('accounts')->insert([
                    'tenant_id' => $tenantId,
                    'acc_no' => $account->acc_no,
                    'name' => $account->name,
                    'type' => $account->type ?? '',
                    'opening_balance' => 0,
                    'budget' => 0,
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_templates');
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
