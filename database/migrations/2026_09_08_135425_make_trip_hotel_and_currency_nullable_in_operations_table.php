<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operations', function (Blueprint $table) {

            $table->foreignId('trip_hotel_id')
                ->nullable()
                ->change();

            $table->foreignId('id_currency')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('operations', function (Blueprint $table) {

            $table->foreignId('trip_hotel_id')
                ->nullable(false)
                ->change();

            $table->foreignId('id_currency')
                ->nullable(false)
                ->change();
        });
    }
};

