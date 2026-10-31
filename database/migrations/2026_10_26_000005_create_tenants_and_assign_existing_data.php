<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tenantTables = [
        'users',
        'customer_profiles',
        'departments',
        'staff_profiles',
        'companies',
        'customers',
        'suppliers',
        'trips',
        'hotels',
        'trips_hotels',
        'operations',
        'extras',
        'operation_extras',
        'prices_company',
        'tickets',
        'boats',
        'vehicles',
        'vehicle_types',
        'drives',
        'vehicle_drivers',
        'driver_types',
        'boat_drivers',
        'operation_assignments',
        'flights',
        'driver_boats',
        'accounts',
        'regions',
        'journal_entries',
        'journal_lines',
        'settings',
        'audit_log',
        'employee_custody_deposits',
        'staff_region_assignments',
    ];

    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        $tenantId = DB::table('tenants')->insertGetId([
            'name' => 'الشركة الرئيسية',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($this->tenantTables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                }

                if ($tableName === 'users' && ! Schema::hasColumn('users', 'is_super_admin')) {
                    $table->boolean('is_super_admin')->default(false);
                }
            });

            DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        }

        foreach ([
            'accounts' => 'acc_no',
            'operations' => 'booking_id',
            'journal_entries' => 'movement_no',
            'regions' => 'name',
        ] as $tableName => $column) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->dropUnique([$column]);
                $table->unique(['tenant_id', $column]);
            });
        }

        foreach (['code', 'registration_number'] as $column) {
            Schema::table('boats', function (Blueprint $table) use ($column) {
                $table->dropUnique([$column]);
                $table->unique(['tenant_id', $column]);
            });
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropPrimary();
            $table->bigIncrements('id');
            $table->unique(['tenant_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'key']);
            $table->dropPrimary();
            $table->dropColumn('id');
            $table->primary('key');
        });

        foreach ([
            'accounts' => 'acc_no',
            'operations' => 'booking_id',
            'journal_entries' => 'movement_no',
            'regions' => 'name',
        ] as $tableName => $column) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->dropUnique(['tenant_id', $column]);
                $table->unique($column);
            });
        }

        foreach (['code', 'registration_number'] as $column) {
            Schema::table('boats', function (Blueprint $table) use ($column) {
                $table->dropUnique(['tenant_id', $column]);
                $table->unique($column);
            });
        }

        foreach (array_reverse($this->tenantTables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropConstrainedForeignId('tenant_id');

                if ($tableName === 'users' && Schema::hasColumn('users', 'is_super_admin')) {
                    $table->dropColumn('is_super_admin');
                }
            });
        }

        Schema::dropIfExists('tenants');
    }
};
