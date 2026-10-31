<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_id')->constrained('operations')->cascadeOnDelete();
            $table->enum('type', ['boat', 'vehicle']);
            $table->unsignedBigInteger('resource_id');
            // References drives.id. It is nullable because a resource may be planned before its driver is chosen.
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->date('assign_date')->index();
            $table->timestamps();

            $table->unique(
                ['operation_id', 'type', 'resource_id', 'assign_date'],
                'operation_assignments_unique_assignment'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_assignments');
    }
};
