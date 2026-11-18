<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tenants', 'api_key_encrypted')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->dropColumn('api_key_encrypted');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->text('api_key_encrypted')->nullable();
        });
    }
};
