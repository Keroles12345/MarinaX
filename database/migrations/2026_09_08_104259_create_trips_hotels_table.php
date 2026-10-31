<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('trips_hotels', function (Blueprint $table) {
    $table->id();

    $table->foreignId('trip_id')
        ->constrained('trips')
        ->cascadeOnDelete();

    $table->foreignId('hotel_id')
        ->constrained('hotels')
        ->cascadeOnDelete();

    $table->time('pickup_time')->nullable();

    $table->boolean('status')->default(true);

    $table->timestamps();

    $table->unique(['trip_id', 'hotel_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips_hotels');
    }
};
