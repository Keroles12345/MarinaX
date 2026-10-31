<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->foreignId('region_id')
                ->nullable()
                ->after('id')
                ->constrained('regions')
                ->nullOnDelete();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('region_id')
                ->nullable()
                ->after('id')
                ->constrained('regions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });

        Schema::table('boats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
