<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::create([
            'name' => 'Office of Medical Center Chief',
            'code' => 'OMCC'
        ]);
        
        Department::create([
            'name' => 'Medical Services/Arcillary',
            'code' => 'MS'
        ]);
        
        Department::create([
            'name' => 'Hospital Operations & Patient Support Services',
            'code' => 'HOPPS'
        ]);
        
        Department::create([
            'name' => 'Nursing Services',
            'code' => 'NS'
        ]);
        
        Department::create([
            'name' => 'Finance Services',
            'code' => 'FS'
        ]);
    }
}
