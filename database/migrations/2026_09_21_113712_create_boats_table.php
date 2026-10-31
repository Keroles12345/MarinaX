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
      Schema::create('boats', function (Blueprint $table) {
    $table->id();

    $table->foreignId('company_id')
          ->constrained('companies')
          ->cascadeOnDelete();

    $table->string('name', 100);
    $table->string('code', 50)->unique();
    $table->integer('capacity')->default(0);
    $table->string('type', 50)->nullable();
    $table->string('registration_number', 100)->nullable()->unique();
    $table->boolean('is_active')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boats');
    }
};
