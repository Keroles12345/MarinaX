<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operations', function (Blueprint $table) {

            // حذف الـ Foreign Key أولًا
            $table->dropForeign(['id_customer']);

            // ثم حذف العمود
            $table->dropColumn('id_customer');
        });
    }

    public function down(): void
    {
        Schema::table('operations', function (Blueprint $table) {

            $table->foreignId('id_customer')
                ->constrained('customers')
                ->restrictOnDelete();
        });
    }
};

