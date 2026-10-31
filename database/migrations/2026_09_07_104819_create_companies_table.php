<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {

            $table->id();

            // اسم الشركة
            $table->string('name');

            // العنوان
            $table->text('address')->nullable();

            // الهاتف
            $table->string('phone', 50)->nullable();

            // البريد الإلكتروني
            $table->string('email')->nullable();

            // الحالة: مفعل / معطل
            $table->boolean('status')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
