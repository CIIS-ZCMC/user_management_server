<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\DepartmentGroup;

class DepartmentGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DepartmentGroup::create([
            'uuid' => Str::uuid(),
            'code' => '001',
            'name' => 'Finance Division'
        ]);
        
        DepartmentGroup::create([
            'uuid' => Str::uuid(),
            'code' => '002',
            'name' => 'Medical Division'
        ]);
        
        DepartmentGroup::create([
            'uuid' => Str::uuid(),
            'code' => '003',
            'name' => 'Nursing Division'
        ]);
        
        DepartmentGroup::create([
            'uuid' => Str::uuid(),
            'code' => '004',
            'name' => 'HOPSS'
        ]);
    }
}
