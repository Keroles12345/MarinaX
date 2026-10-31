<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_drivers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vehicle_id')
                  ->constrained('vehicles')
                  ->cascadeOnDelete();

            $table->foreignId('driver_id')
                  ->constrained('drives')
                  ->cascadeOnDelete();

            $table->enum('assignment_role', [
                'master',
                'spare'
            ])->default('master');

            $table->timestamps();

            $table->unique(['vehicle_id', 'driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_drivers');
    }
};
