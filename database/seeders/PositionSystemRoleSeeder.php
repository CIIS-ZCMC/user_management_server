<?php

namespace Database\Seeders;

use App\Models\EmployeeProfile;
use App\Models\SpecialAccessRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
        $regular_employee =  SystemRole::find(1);
        $designations = Designation::all();

        foreach($designations as $designation){
            PositionSystemRole::create([
                'designation_id' => $designation->id,
                'system_role_id' => $regular_employee -> id,
            ]);
        }
    }
}
