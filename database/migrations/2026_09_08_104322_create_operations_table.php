<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations', function (Blueprint $table) {

            $table->id();


            $table->string('booking_id', 30)->unique();



            $table->foreignId('id_trip')
                ->constrained('trips')
                ->restrictOnDelete();

            $table->foreignId('id_company')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->foreignId('id_customer')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->foreignId('trip_hotel_id')
                ->nullable()
                ->constrained('trips_hotels')
                ->nullOnDelete();

            $table->foreignId('id_currency')
                ->constrained('currencies')
                ->restrictOnDelete();


            $table->date('trip_date');

            $table->time('trip_time')->nullable();



            $table->time('time_pickup')->nullable();



            $table->string('number_room', 50)->nullable();



            $table->integer('adults')->default(0);

            $table->integer('children')->default(0);

            $table->integer('infants')->default(0);



            $table->decimal('price_adult', 10, 2)->default(0);

            $table->decimal('price_child', 10, 2)->default(0);

            $table->decimal('price_infant', 10, 2)->default(0);



            $table->decimal('subtotal', 10, 2)->default(0);

            $table->decimal('amount_extra', 10, 2)->default(0);

            $table->decimal('amount_discount', 10, 2)->default(0);



            $table->decimal('rate_tax', 5, 2)->default(0);

            $table->decimal('amount_tax', 10, 2)->default(0);



            $table->decimal('amount_total', 10, 2)->default(0);



            $table->string('method_payment', 50)->nullable();

            $table->string('status_payment', 50)->default('pending');

            $table->decimal('amount_paid', 10, 2)->default(0);

            $table->decimal('amount_remaining', 10, 2)->default(0);



            $table->text('remarks')->nullable();



            $table->string('status', 50)->default('pending');


            
            $table->foreignId('by_created')
                ->constrained('users')
                ->restrictOnDelete();


            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
