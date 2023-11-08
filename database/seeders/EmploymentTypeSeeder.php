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
            'name' => "Full-Time Employment"
        ]);
        
        EmploymentType::create([
            'name' => "Part-Time Employment"
        ]);
        
        EmploymentType::create([
            'name' => "Contractual Employment"
        ]);
        
        EmploymentType::create([
            'name' => "Temporary Employment"
        ]);
        
        EmploymentType::create([
            'name' => "Internship"
        ]);
        
        EmploymentType::create([
            'name' => "Apprenticeship"
        ]);
        
        EmploymentType::create([
            'name' => "Seasonal Employment"
        ]);
        
        EmploymentType::create([
            'name' => "Project-Based Employment"
        ]);
        
        EmploymentType::create([
            'name' => "Commission-Based Employment"
        ]);
        
        EmploymentType::create([
            'name' => "Volunteer Work"
        ]);
        
        EmploymentType::create([
            'name' => "Resigned"
        ]);
        
        EmploymentType::create([
            'name' => "Terminated "
        ]);
        
        EmploymentType::create([
            'name' => "Retired"
        ]);
        
        /**let go due to company restructuring or downsizing */
        EmploymentType::create([
            'name' => "Laid off"
        ]);
        
        EmploymentType::create([
            'name' => "Contract Ended"
        ]);
    }
}
