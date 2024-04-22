<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmploymentType;

class EmploymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmploymentType::create([
            'name' => "Permanent Full-time"
        ]);

        EmploymentType::create([
            'name' => "Permanent Part-time"
        ]);
        
        EmploymentType::create([
            'name' => "Permanent CTI"
        ]);

        EmploymentType::create([
            'name' => "Temporary"
        ]);

        EmploymentType::create([
            'name' => "Job Order"
        ]);
    }
}
