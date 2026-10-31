<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class ApplicationTestDataSeeder extends Seeder
{
    private const DATABASE = 'mysystem';

    public function run(): void
    {
        if (DB::connection()->getDatabaseName() !== self::DATABASE) {
            throw new RuntimeException('Application test data may only be added to the mysystem database.');
        }

        DB::transaction(function (): void {
            $now = now();
            $columnsByTable = [];

            $record = static function (string $table, array $key, array $values) use ($now, &$columnsByTable): int {
                $columnsByTable[$table] ??= Schema::getColumnListing($table);
                $columns = $columnsByTable[$table];

                if (in_array('created_at', $columns, true)) {
                    $values['created_at'] ??= $now;
                }

                if (in_array('updated_at', $columns, true)) {
                    $values['updated_at'] ??= $now;
                }

                $query = DB::table($table)->where($key);
                $existing = $query->first();

                if ($existing) {
                    $query->update($values);

                    return (int) $existing->id;
                }

                DB::table($table)->insert($key + $values);

                return (int) DB::table($table)->where($key)->value('id');
            };

            $departmentId = $record('departments', ['name' => 'قسم اختبار البيانات'], [
                'description' => 'قسم تجريبي لاستخدام بيانات الاختبار',
            ]);

            $customerUserId = $record('users', ['email' => 'customer-test@example.test'], [
                'name' => 'عميل اختبار البيانات',
                'password' => Hash::make(Str::random(64)),
                'role' => 'staff',
                'status' => false,
            ]);

            $staffUserId = $record('users', ['email' => 'staff-test@example.test'], [
                'name' => 'موظف اختبار البيانات',
                'password' => Hash::make(Str::random(64)),
                'role' => 'staff',
                'status' => false,
            ]);

            $record('customer_profiles', ['user_id' => $customerUserId], [
                'phone' => '+201000000001',
                'address' => 'عنوان تجريبي - القاهرة',
                'company_name' => 'شركة العميل التجريبي',
            ]);

            $record('staff_profiles', ['user_id' => $staffUserId], [
                'department_id' => $departmentId,
                'phone' => '+201000000002',
                'position' => 'موظف تجريبي',
            ]);

            $companyId = $record('companies', ['name' => 'شركة اختبار البيانات'], [
                'address' => 'القاهرة',
                'phone' => '+201000000003',
                'email' => 'company-test@example.test',
                'status' => true,
            ]);

            $currencyId = $record('currencies', ['code' => 'TST'], [
                'name' => 'عملة اختبار',
                'symbol' => 'TST',
                'exchange_rate' => 1,
                'is_default' => false,
                'is_active' => true,
            ]);

            $customerId = $record('customers', ['name' => 'عميل اختبار البيانات'], [
                'email' => 'customer-record-test@example.test',
                'phone' => '+201000000004',
                'address' => 'عنوان تجريبي - الجيزة',
                'notes' => 'سجل تجريبي',
            ]);

            $supplierId = $record('suppliers', ['name' => 'مورد اختبار البيانات'], [
                'address' => 'الغردقة',
                'phone' => '+201000000005',
                'email' => 'supplier-test@example.test',
                'status' => true,
            ]);

            $tripId = $record('trips', ['name' => 'رحلة اختبار البيانات'], [
                'time_trip' => '08:30:00',
                'description' => 'رحلة تجريبية',
                'status' => true,
            ]);

            $hotelId = $record('hotels', ['name' => 'فندق اختبار البيانات'], [
                'address' => 'الغردقة',
                'phone' => '+201000000006',
                'email' => 'hotel-test@example.test',
                'status' => true,
            ]);

            $tripHotelId = $record('trips_hotels', [
                'trip_id' => $tripId,
                'hotel_id' => $hotelId,
            ], [
                'pickup_time' => '08:00:00',
                'status' => true,
            ]);

            $extraId = $record('extras', ['name' => 'خدمة اختبار البيانات'], [
                'price' => 25,
                'rate_tax' => 0,
                'status' => true,
            ]);

            $operationId = $record('operations', ['booking_id' => 'TEST-BOOKING-20261019-001'], [
                'id_trip' => $tripId,
                'id_company' => $companyId,
                'customer_id' => $customerId,
                'supplier_id' => $supplierId,
                'trip_hotel_id' => $tripHotelId,
                'id_currency' => $currencyId,
                'trip_date' => today()->addDays(7)->toDateString(),
                'adults' => 2,
                'children' => 0,
                'infants' => 0,
                'price_adult' => 100,
                'subtotal' => 200,
                'amount_total' => 200,
                'totalamount' => 200,
                'status_payment' => 'pending',
                'amount_paid' => 0,
                'amount_remaining' => 200,
                'status' => 'pending',
                'by_created' => $staffUserId,
            ]);

            $record('operation_extras', [
                'operation_id' => $operationId,
                'extra_id' => $extraId,
            ], [
                'price' => 25,
                'quantity' => 1,
                'amount_total' => 25,
            ]);

            $record('prices_company', [
                'id_company' => $companyId,
                'id_trip' => $tripId,
                'id_currency' => $currencyId,
            ], [
                'price_adult' => 100,
                'price_child' => 50,
                'price_infant' => 0,
            ]);

            $record('tickets', ['ticket_code' => 'TEST-TICKET-20261019-001'], [
                'operation_id' => $operationId,
                'token' => hash('sha256', 'application-test-data-ticket-001'),
                'status' => 'active',
                'expires_at' => now()->addDays(30),
            ]);

            $vehicleTypeId = $record('vehicle_types', ['name' => 'نوع مركبة اختبار'], []);
            $vehicleId = $record('vehicles', ['code' => 'TEST-VEHICLE-001'], [
                'name' => 'مركبة اختبار البيانات',
                'status' => 'active',
                'codes' => 'TEST-VEHICLE-001',
                'color' => 'أبيض',
                'vehicle_type_id' => $vehicleTypeId,
            ]);

            $driverTypeId = $record('driver_types', ['name' => 'نوع سائق اختبار'], []);
            $driverId = $record('drives', ['email' => 'driver-test@example.test'], [
                'name' => 'سائق اختبار البيانات',
                'phone' => '+201000000007',
                'license_number' => 'TEST-LICENSE-001',
                'create_at' => $now,
                'status' => 'available',
                'LicenseDate' => today()->toDateString(),
                'driver_type_id' => $driverTypeId,
            ]);

            $boatId = $record('boats', ['code' => 'TEST-BOAT-001'], [
                'company_id' => $companyId,
                'name' => 'قارب اختبار البيانات',
                'capacity' => 10,
                'type' => 'اختبار',
                'registration_number' => 'TEST-REG-001',
                'is_active' => true,
            ]);

            $record('driver_boats', ['name' => 'سائق قارب اختبار البيانات'], [
                'phone' => '+201000000008',
                'boat_id' => $boatId,
                'license_number' => 'TEST-BOAT-LICENSE-001',
                'is_active' => true,
            ]);

            $record('boat_drivers', [
                'boat_id' => $boatId,
                'driver_id' => $driverId,
            ], [
                'assignment_role' => 'master',
            ]);

            $record('vehicle_drivers', [
                'vehicle_id' => $vehicleId,
                'driver_id' => $driverId,
            ], [
                'assignment_role' => 'master',
            ]);

            $record('operation_assignments', [
                'operation_id' => $operationId,
                'type' => 'boat',
                'resource_id' => $boatId,
                'assign_date' => today()->addDays(7)->toDateString(),
            ], [
                'driver_id' => $driverId,
            ]);

            if (! DB::table('flights')->exists()) {
                $flightValues = [];
                $flightColumns = $columnsByTable['flights'] ??= Schema::getColumnListing('flights');

                if (in_array('created_at', $flightColumns, true)) {
                    $flightValues['created_at'] = $now;
                }

                if (in_array('updated_at', $flightColumns, true)) {
                    $flightValues['updated_at'] = $now;
                }

                DB::table('flights')->insert($flightValues);
            }
        });
    }
}
