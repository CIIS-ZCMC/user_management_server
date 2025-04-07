<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\SystemRolePermission;
use App\Models\SystemRole;
use App\Models\Permission;

class SystemRolePermisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $system_role = SystemRole::where('name', 'Super Admin')->first();
        $create_user = Permission::where('code', 'CU1')->first();
        $view_user = Permission::where('code', 'VU1')->first();
        $update_user = Permission::where('code', 'UU1')->first();
        $delete_user = Permission::where('code', 'DU1')->first();

        SystemRolePermission::create([
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $create_user -> id
        ]);

        SystemRolePermission::create([
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $view_user -> id
        ]);

        SystemRolePermission::create([
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $update_user -> id
        ]);
        
        SystemRolePermission::create([
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $delete_user -> id
        ]);
        
        SystemRolePermission::create([
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $create_user -> id
        ]);
        
        SystemRolePermission::create([
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $view_user -> id
        ]);

        SystemRolePermission::create([
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $update_user -> id
        ]);
        
        SystemRolePermission::create([
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $delete_user -> id
        ]);

        SystemRolePermission::create([
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $create_user -> id
        ]);
        
        SystemRolePermission::create([
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $view_user -> id
        ]);
        
        SystemRolePermission::create([
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $update_user -> id
        ]);
        
        SystemRolePermission::create([
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $delete_user -> id
        ]);
        
        $system_role = SystemRole::where('name', 'Staff')->first();

        SystemRolePermission::create([
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> id,
            'permission_id' => $view_user -> id
        ]);
    }
}
