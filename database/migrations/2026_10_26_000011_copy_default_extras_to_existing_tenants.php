<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sourceTenantId = DB::table('tenants')
            ->where('name', 'الشركة الرئيسية')
            ->value('id');

        if (! $sourceTenantId) {
            return;
        }

        $sourceExtras = DB::table('extras')
            ->where('tenant_id', $sourceTenantId)
            ->get(['name', 'price', 'rate_tax', 'status']);

        foreach (DB::table('tenants')->where('id', '<>', $sourceTenantId)->pluck('id') as $tenantId) {
            foreach ($sourceExtras as $extra) {
                $exists = DB::table('extras')
                    ->where('tenant_id', $tenantId)
                    ->where('name', $extra->name)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('extras')->insert([
                    'tenant_id' => $tenantId,
                    'name' => $extra->name,
                    'price' => $extra->price,
                    'rate_tax' => $extra->rate_tax,
                    'status' => $extra->status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep tenant-owned extras because bookings may already reference them.
    }
};
