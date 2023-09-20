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
            'uuid' => Str::uuid(),
            'code' => '001',
            'name' => 'Finance Division'
        ]);
        
        Division::create([
            'uuid' => Str::uuid(),
            'code' => '002',
            'name' => 'Medical Division'
        ]);
        
        Division::create([
            'uuid' => Str::uuid(),
            'code' => '003',
            'name' => 'Nursing Division'
        ]);
        
        Division::create([
            'uuid' => Str::uuid(),
            'code' => '004',
            'name' => 'HOPSS'
        ]);
    }
}
