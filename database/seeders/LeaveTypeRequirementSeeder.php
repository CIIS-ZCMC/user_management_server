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
        //1
        Requirement::create([
            'name' => 'Leave Application Form - Prescribed CSC Form no. 6 revised 2020',
            'description' => null
        ]);
        
        //2
        Requirement::create([
            'name' => 'CSC Medical Certificate form',
            'description' => null
        ]);
        
        //3
        Requirement::create([
            'name' => 'Clearance certificate',
            'description' => null
        ]);

         //4
        Requirement::create([
            'name' => 'Notice of Allocation of Maternity Leave (CSC form no. 6a,s. 2020)',
            'description' => null
        ]);

         //5
        Requirement::create([
            'name' => 'Duly notarized Training Agreement (Contract)',
            'description' => null
        ]);

         //6
        Requirement::create([
            'name' => 'Approved Letter of Request from MCC',
            'description' => null
        ]);

         //7
        Requirement::create([
            'name' => 'Pre - Adoptive Placement Authority (Authentic copy from DSWD)',
            'description' => null
        ]);

         //8
        Requirement::create([
            'name' => 'Decree of Adoption (Authentic copy issued by court)',
            'description' => null
        ]);

         //9
        Requirement::create([
            'name' => 'Barangay Protection Order (BPO)',
            'description' => null
        ]);

         //10
        Requirement::create([
            'name' => 'Temporary/Permanent Protection Order (TPO/PPO) – from the courts',
            'description' => null
        ]);

         //11
        Requirement::create([
            'name' => 'Certification of the Punong Barangay or Kagawad or Prosecutor',
            'description' => null
        ]);

         //12
        Requirement::create([
            'name' => 'Fit to Work from the attending Physician',
            'description' => null
        ]);
       
    }
}
