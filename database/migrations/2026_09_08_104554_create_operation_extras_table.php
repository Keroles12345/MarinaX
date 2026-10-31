<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_extras', function (Blueprint $table) {
            $table->id();

            // العملية
            $table->foreignId('operation_id')
                ->constrained('operations')
                ->cascadeOnDelete();

            // الإضافة
            $table->foreignId('extra_id')
                ->constrained('extras')
                ->restrictOnDelete();

            // Snapshot لسعر الإضافة وقت اختيارها
            $table->decimal('price', 10, 2)->default(0);

            // الكمية لو الإضافة ممكن تتكرر
            $table->integer('quantity')->default(1);

            // إجمالي الإضافة
            $table->decimal('amount_total', 10, 2)->default(0);

            $table->timestamps();

            // منع تكرار نفس الإضافة في نفس العملية
            $table->unique(['operation_id', 'extra_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_extras');
    }
};

