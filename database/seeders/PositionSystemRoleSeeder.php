<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\EmploymentPosition;
use App\Models\PositionSystemRole;

class PositionSystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PositionSystemRole::create([
            'employment_position_id' => EmploymentPosition::where('abbreviation', 'SA1')->first()->id,
            'system_role_id' => 10000,
        ]);
        
        PositionSystemRole::create([
            'employment_position_id' => EmploymentPosition::where('abbreviation', 'CP3')->first()->id,
            'system_role_id' => 10000,
        ]);
    }
}
