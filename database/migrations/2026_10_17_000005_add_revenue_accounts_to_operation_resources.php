<?php

use App\Services\OperationRevenueAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boats', function (Blueprint $table): void {
            $table->foreignId('revenue_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
        });
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->string('ownership_type', 20)->default('internal')->after('status');
            $table->foreignId('revenue_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
        });

        if (Schema::hasColumn('boats', 'revenue_account_id') && Schema::hasColumn('vehicles', 'revenue_account_id')) {
            DB::transaction(function (): void {
                $revenueAccounts = app(OperationRevenueAccountService::class);

                foreach (DB::table('boats')->orderBy('id')->get(['id', 'name', 'ownership_type']) as $boat) {
                    $revenueAccounts->ensure('boat', (int) $boat->id, $boat->name, $boat->ownership_type);
                }
                foreach (DB::table('vehicles')->orderBy('id')->get(['id', 'name', 'ownership_type']) as $vehicle) {
                    $revenueAccounts->ensure('vehicle', (int) $vehicle->id, $vehicle->name, $vehicle->ownership_type);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropForeign(['revenue_account_id']);
            $table->dropColumn(['revenue_account_id', 'ownership_type']);
        });
        Schema::table('boats', function (Blueprint $table): void {
            $table->dropForeign(['revenue_account_id']);
            $table->dropColumn('revenue_account_id');
        });
    }
};
