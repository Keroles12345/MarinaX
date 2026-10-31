<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->foreignId('region_id')
                ->nullable()
                ->after('id')
                ->constrained('regions')
                ->nullOnDelete();
        });

        Schema::create('staff_region_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_region_assignments');

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
