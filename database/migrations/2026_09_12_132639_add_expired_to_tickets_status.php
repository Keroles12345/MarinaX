<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    DB::statement("
        ALTER TABLE tickets
        MODIFY status ENUM('active', 'used', 'cancelled', 'expired')
        NOT NULL DEFAULT 'active'
    ");
}

public function down(): void
{
    DB::statement("
        ALTER TABLE tickets
        MODIFY status ENUM('active', 'used', 'cancelled')
        NOT NULL DEFAULT 'active'
    ");
}
};
