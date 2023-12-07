<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\Designation;
use App\Models\PositionSystemRole;
use App\Models\SystemRole;

class PositionSystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $system_role =  SystemRole::find(1);

        PositionSystemRole::create([
            'designation_id' => Designation::where('code', 'SA I')->first()->id,
            'system_role_id' => $system_role -> id,
        ]);
        
        PositionSystemRole::create([
            'designation_id' => Designation::where('code', 'CP III')->first()->id,
            'system_role_id' => $system_role -> id,
        ]);
    }
}
