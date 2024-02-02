<?php

namespace Database\Seeders;

use App\Models\EmployeeProfile;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignSpecialAccessToSuperAdmin extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super_admin =  SystemRole::find(1);

        SpecialAccessRole::create([
            'system_role_id' => $super_admin->id,
            'employee_profile_id' => EmployeeProfile::where('employee_id', '2022091351')->first()->id
        ]);
    }
}
