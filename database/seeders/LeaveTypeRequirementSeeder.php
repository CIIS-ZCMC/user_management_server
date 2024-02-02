<?php

namespace Database\Seeders;

use App\Models\Requirement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveTypeRequirementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Vacation leave & SPL
        Requirement::create([
            'name' => 'Leave Application Form - Prescribed CSC Form no. 6 revised 2020',
            'description' => null
        ]);
        
        //Sick leave
        Requirement::create([
            'name' => 'CSC Medical Certificate form',
            'description' => null
        ]);
        
        //Maternity leave
        Requirement::create([
            'name' => 'Clearance certificate',
            'description' => null
        ]);
    }
}
