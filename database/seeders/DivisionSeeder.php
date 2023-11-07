<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Division;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        Division::create([
            'name' => 'Office of Medical Center Chief',
            'code' => 'OMCC',
            'job_specification' => 'MCC II' //Medical Center Chief
        ]);
        
        Division::create([
            'name' => 'Medical Service',
            'code' => 'MS',
            'job_specification' => 'CMPS II' //Chief Medical Professional Staff II
        ]);
        
        Division::create([
            'name' => 'Hospital Operations & Patient Support Services',
            'code' => 'HOPPS',
            'job_specification' => 'CAO' // Chief Administrative Officer
        ]);
        
        Division::create([
            'name' => 'Nursing Service',
            'code' => 'NS',
            'job_specification' => 'N-VII' // Nurse VII or Nurse Manager
        ]);
        
        Division::create([
            'name' => 'Finance Service',
            'code' => 'FS',
            'job_specification' => 'FINMO II' // Financial Management Officer II
        ]);
    }
}
