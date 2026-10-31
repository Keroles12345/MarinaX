<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['customer', 'staff', 'supervisor', 'admin', 'accountant', 'viewer', 'account_user'])
                ->default('staff')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'account_user')->update(['role' => 'viewer']);

        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['customer', 'staff', 'supervisor', 'admin', 'accountant', 'viewer'])
                ->default('staff')
                ->change();
        });
    }
};
