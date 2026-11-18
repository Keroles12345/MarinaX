<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
            ALTER TABLE tickets
            MODIFY status ENUM('active', 'used', 'cancelled', 'expired')
            NOT NULL DEFAULT 'active'
        ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
            ALTER TABLE tickets
            MODIFY status ENUM('active', 'used', 'cancelled')
            NOT NULL DEFAULT 'active'
        ");
        }
    }
};
