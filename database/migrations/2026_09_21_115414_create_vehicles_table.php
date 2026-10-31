<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);

            $table->string('code', 50);

            $table->string('status', 20)->default('active');

            $table->timestamp('created_at')->nullable();

            $table->string('codes', 50)->nullable();

            $table->string('color', 50)->nullable();

            $table->foreignId('vehicle_type_id')
                  ->constrained('vehicle_types')
                  ->restrictOnDelete();

            $table->unsignedBigInteger('account_id')->nullable();

            $table->string('codee', 50)->nullable();

      
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
