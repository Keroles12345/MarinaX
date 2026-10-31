<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drives', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);

            $table->string('phone', 30)->nullable();

            $table->string('license_number', 100)->nullable();

            $table->timestamp('create_at')->nullable();

            $table->string('status', 20)->default('available');

            $table->unsignedBigInteger('account_id')->nullable();

            $table->date('LicenseDate')->nullable();

            $table->string('degreeOfLeave', 100)->nullable();

            $table->string('email', 150)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drives');
    }
};
