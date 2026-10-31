<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $canonicalIds = [];
        foreach (DB::table('vehicle_types')->orderBy('id')->get(['id', 'name']) as $type) {
            $key = mb_strtolower(trim($type->name));
            if (! isset($canonicalIds[$key])) {
                $canonicalIds[$key] = (int) $type->id;

                continue;
            }

            $canonicalId = $canonicalIds[$key];
            DB::table('vehicles')->where('vehicle_type_id', $type->id)->update([
                'vehicle_type_id' => $canonicalId,
            ]);
            DB::table('vehicle_types')->where('id', $type->id)->delete();
        }

        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
        });
    }
};
