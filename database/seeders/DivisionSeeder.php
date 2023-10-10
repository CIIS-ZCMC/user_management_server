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
            'code' => 'OMCC'
        ]);
        
        Division::create([
            'name' => 'Medical Services/Arcillary',
            'code' => 'MS'
        ]);
        
        Division::create([
            'name' => 'Hospital Operations & Patient Support Services',
            'code' => 'HOPPS'
        ]);
        
        Division::create([
            'name' => 'Nursing Services',
            'code' => 'NS'
        ]);
        
        Division::create([
            'name' => 'Finance Services',
            'code' => 'FS'
        ]);
    }
}
