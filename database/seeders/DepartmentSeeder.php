<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\departments;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        departments::create([
            'name' => 'Sales',
            'description' => 'Sales Department',
        ]);

        departments::create([
            'name' => 'Operations',
            'description' => 'Operations Department',
        ]);

        departments::create([
            'name' => 'Reseller',
            'description' => 'Reseller Department',
        ]);
    }
}
