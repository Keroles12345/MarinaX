<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boat_drivers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('boat_id')
                  ->constrained('boats')
                  ->cascadeOnDelete();

            $table->foreignId('driver_id')
                  ->constrained('drives')
                  ->cascadeOnDelete();

            $table->enum('assignment_role', [
                'master',
                'spare'
            ])->default('master');

            $table->timestamps();

            $table->unique(['boat_id', 'driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boat_drivers');
    }
};
