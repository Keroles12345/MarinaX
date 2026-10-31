<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'مورد تجريبي - النيل', 'address' => 'القاهرة', 'phone' => null, 'email' => 'nile-supplier@example.test'],
            ['name' => 'مورد تجريبي - البحر الأحمر', 'address' => 'الغردقة', 'phone' => null, 'email' => 'redsea-supplier@example.test'],
            ['name' => 'مورد تجريبي - مرسى علم', 'address' => 'مرسى علم', 'phone' => null, 'email' => 'marsa-supplier@example.test'],
        ] as $supplier) {
            Supplier::updateOrCreate(
                ['name' => $supplier['name']],
                [...$supplier, 'status' => true]
            );
        }
    }
}
