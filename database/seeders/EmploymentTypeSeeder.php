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
        Employment::create([
            'name' => "Full-Time Employment"
        ]);
        
        Employment::create([
            'name' => "Part-Time Employment"
        ]);
        
        Employment::create([
            'name' => "Contractual Employment"
        ]);
        
        Employment::create([
            'name' => "Temporary Employment"
        ]);
        
        Employment::create([
            'name' => "Internship"
        ]);
        
        Employment::create([
            'name' => "Apprenticeship"
        ]);
        
        Employment::create([
            'name' => "Seasonal Employment"
        ]);
        
        Employment::create([
            'name' => "Project-Based Employment"
        ]);
        
        Employment::create([
            'name' => "Commission-Based Employment"
        ]);
        
        Employment::create([
            'name' => "Volunteer Work"
        ]);
        
        Employment::create([
            'name' => "Resigned"
        ]);
        
        Employment::create([
            'name' => "Terminated "
        ]);
        
        Employment::create([
            'name' => "Retired"
        ]);
        
        /**let go due to company restructuring or downsizing */
        Employment::create([
            'name' => "Laid off"
        ]);
        
        Employment::create([
            'name' => "Contract Ended"
        ]);
    }
}
