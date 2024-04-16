<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\Department;
use App\Models\Division;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        

        /**
         * Hospital Operations and Patient Support Service
         */

        $hopps = Division::where('code', 'NURSING')->first();

        Department::create([
            'name' => 'Clinical Nursing Wards',
            'code' => 'NSO-CLINIC',
            'division_id' => $hopps -> id
        ]);
        
        Department::create([
            'name' => 'Out-Patient Department',
            'code' => 'NSO-OPD',
            'division_id' => $hopps -> id
        ]);
        
        Department::create([
            'name' => 'Operating Room Complex',
            'code' => 'NSO-OR',
            'division_id' => $hopps -> id
        ]);
        
        Department::create([
            'name' => 'Emergency Room and Critical Care',
            'code' => 'NSO-ER',
            'division_id' => $hopps -> id
        ]);
        
        Department::create([
            'name' => 'Hemodialysis and Peritoneal Dialysis',
            'code' => 'NSO-HPD',
            'division_id' => $hopps -> id
        ]);
        
        Department::create([
            'name' => 'Delivery Room',
            'code' => 'NSO-DR',
            'division_id' => $hopps -> id
        ]);
        
        Department::create([
            'name' => 'Central Supply and Sterilization',
            'code' => 'NSO-CSS',
            'division_id' => $hopps -> id
        ]);
        
        Department::create([
            'name' => 'Special Care Areas',
            'code' => 'NSO-SCA',
            'division_id' => $hopps -> id
        ]);
    }
}
