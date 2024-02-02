<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use App\Models\System;
use App\Models\SystemRole;
use App\Models\ModulePermission;
use App\Models\RoleModulePermission;

class SystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $system = System::WHERE("code",  env('SYSTEM_ABBREVIATION'))->first();

        $super_admin_role = Role::where('code',"super_admin")->first();

        $super_admin = SystemRole::create([
            'role_id' => $super_admin_role->id,
            'system_id' => $system -> id
        ]);

        $super_admin_access_rights =  ModulePermission::where('code', 'LIKE', '%UMIS-SM%')->orWhere('code', 'LIKE', '%UMIS-EM%')->get();

        foreach($super_admin_access_rights as $key => $access_right)
        {
            RoleModulePermission::create([
                'system_role_id' => $super_admin['id'],
                'module_permission_id' => $access_right['id']
            ]);
        }
        
        $regular_employee_role = Role::where('code',"reg_emp")->first();

        $regular_employee = SystemRole::create([
            'role_id' => $regular_employee_role->id,
            'system_id' => $system -> id
        ]);
        
        $access_rights = [
            "UMIS-EM view", 
            "UMIS-EM view-all",
            "UMIS-DTRM view",
            "UMIS-LM view-all",
            "UMIS-LM write",
            "UMIS-LM update",
            "UMIS-LM download",
            "UMIS-OB view",
            "UMIS-OB request",
            "UMIS-OT view",
            "UMIS-OT request",
            "UMIS-OM view",
            "UMIS-OM request",
            "UMIS-CT view",
            "UMIS-CT request",
            "UMIS-ScM view",
            "UMIS-PAM view-all",
            "UMIS-PAM write",
            "UMIS-PAM view",
            "UMIS-PAM update",
            "UMIS-PAM request",
        ];

        $regular_employee_access_rights =  ModulePermission::whereIn('code', $access_rights)->get();

        foreach($regular_employee_access_rights as $key => $access_right)
        {
            RoleModulePermission::create([
                'system_role_id' => $regular_employee['id'],
                'module_permission_id' => $access_right['id']
            ]);
        }
    }
}
