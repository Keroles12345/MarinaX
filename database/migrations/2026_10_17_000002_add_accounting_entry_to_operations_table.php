<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->foreignId('accounting_entry_id')
                ->nullable()
                ->unique()
                ->constrained('journal_entries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropForeign(['accounting_entry_id']);
            $table->dropUnique(['accounting_entry_id']);
            $table->dropColumn('accounting_entry_id');
        });
    }
};
