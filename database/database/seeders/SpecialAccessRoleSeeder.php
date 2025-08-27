<?php

namespace Database\Seeders;

use App\Models\EmployeeProfile;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialAccessRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $system_role =  SystemRole::find(1);

        $employee_profile = EmployeeProfile::where('employee_id', '1918091351')->first();
        SpecialAccessRole::create([
            'employee_profile_id' => $employee_profile->id,
            'system_role_id' => $system_role->id,
        ]);

        // $employee_profile = EmployeeProfile::all();

        // foreach ($employee_profile as  $row) {

        //     SpecialAccessRole::create([
        //         'employee_profile_id' => $row->id,
        //         'system_role_id' => $system_role->id,
        //     ]);
        // }
    }
}
