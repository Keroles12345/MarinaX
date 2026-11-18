<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('api_key_prefix', 24)->nullable()->unique();
            $table->string('api_key_hash', 64)->nullable();
        });

        Schema::table('trips', function (Blueprint $table): void {
            $table->json('includes')->nullable();
            $table->json('excludes')->nullable();
            $table->json('faqs')->nullable();
        });

        Schema::create('trip_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'trip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_images');

        Schema::table('trips', function (Blueprint $table): void {
            $table->dropColumn(['includes', 'excludes', 'faqs']);
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropUnique(['api_key_prefix']);
            $table->dropColumn(['api_key_prefix', 'api_key_hash']);
        });
    }
};
