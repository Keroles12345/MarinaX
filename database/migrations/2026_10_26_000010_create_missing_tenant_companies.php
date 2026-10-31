<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('tenants')->get(['id', 'name', 'email', 'phone', 'address', 'status']) as $tenant) {
            $hasCompany = DB::table('companies')
                ->where('tenant_id', $tenant->id)
                ->exists();

            if ($hasCompany) {
                continue;
            }

            DB::table('companies')->insert([
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'status' => $tenant->status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Default company rows are retained because they may already be referenced by bookings.
    }
};
