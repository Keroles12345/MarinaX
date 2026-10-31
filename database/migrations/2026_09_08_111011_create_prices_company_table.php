
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices_company', function (Blueprint $table) {

            $table->id();

            // Company
            $table->foreignId('id_company')
                ->constrained('companies')
                ->restrictOnDelete();

            // Trip
            $table->foreignId('id_trip')
                ->constrained('trips')
                ->restrictOnDelete();

            // Currency
            $table->foreignId('id_currency')
                ->constrained('currencies')
                ->restrictOnDelete();

            // Prices
            $table->decimal('price_adult', 10, 2)->default(0);
            $table->decimal('price_child', 10, 2)->default(0);
            $table->decimal('price_infant', 10, 2)->default(0);

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices_company');
    }
};

