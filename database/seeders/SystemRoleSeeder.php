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

        $role = Role::where('code',"super_admin")->first();

        $super_admin = SystemRole::create([
            'role_id' => $role->id,
            'system_id' => $system -> id
        ]);

        $module_permissions = ModulePermission::all();

        foreach($module_permissions as $key => $module_permission)
        {
            RoleModulePermission::create([
                'system_role_id' => $super_admin['id'],
                'module_permission_id' => $module_permission['id']
            ]);
        }
    }
}
