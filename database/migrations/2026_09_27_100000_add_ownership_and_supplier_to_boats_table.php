<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->string('ownership_type', 20)->default('external')->after('company_id');
            $table->foreignId('supplier_id')->nullable()->after('ownership_type')->constrained('suppliers')->nullOnDelete();
            $table->decimal('supplier_price', 10, 2)->nullable()->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['ownership_type', 'supplier_id', 'supplier_price']);
        });
    }
};
