<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'Super Admin',
            'code' => 'SUPER-USER-00'
        ]);

        Role::create([
            'name' => 'OMCC',
            'code' => 'OMCC-01'
        ]);

        Role::create([
            'name' => 'HR Director',
            'code' => 'HRMO-HEAD-01'
        ]);

        Role::create([
            'name' => 'HR Staff',
            'code' => 'HR-ADMIN'
        ]);

        Role::create([
            'name' => 'Division Head',
            'code' => 'DIV-HEAD-01'
        ]);

        Role::create([
            'name' => 'Department Head',
            'code' => 'DEPT-HEAD-01'
        ]);
        Role::create([
            'name' => 'Section Head',
            'code' => 'SECTION-HEAD-01'
        ]);
        Role::create([
            'name' => 'Unit Head',
            'code' => 'UNIT-HEAD-01'
        ]);
        Role::create([
            'name' => 'Common User - Regular',
            'code' => 'COMMON-REG'
        ]);
        Role::create([
            'name' => 'Common User - JO',
            'code' => 'COMMON-JO'
        ]);
        Role::create([
            'name' => 'Allow Time Adjustment',
            'code' => 'ATA'
        ]);
    }
}
