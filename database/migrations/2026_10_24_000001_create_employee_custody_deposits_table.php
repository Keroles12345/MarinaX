<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_custody_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('deposit_date');
            $table->string('region');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'deposit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_custody_deposits');
    }
};
