<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\SystemRolePermission;
use App\Models\SystemRole;

class SystemRolePermisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemRolePermission::create([
            'action' => 'create',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => 10000
        ]);
        
        SystemRolePermission::create([
            'action' => 'view',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => 10000
        ]);

        SystemRolePermission::create([
            'action' => 'put',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => 10000
        ]);
        
        SystemRolePermission::create([
            'action' => 'delete',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => 10000
        ]);
        
        SystemRolePermission::create([
            'action' => 'create',
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => 10000
        ]);
        
        SystemRolePermission::create([
            'action' => 'view',
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => 10000
        ]);

        SystemRolePermission::create([
            'action' => 'put',
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => 10000
        ]);
        
        SystemRolePermission::create([
            'action' => 'delete',
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => 10000
        ]);
        
        SystemRolePermission::create([
            'action' => 'create',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => 10001
        ]);
        
        SystemRolePermission::create([
            'action' => 'view',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => 10001
        ]);
        
        SystemRolePermission::create([
            'action' => 'put',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => 10001
        ]);
        
        SystemRolePermission::create([
            'action' => 'delete',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => 10001
        ]);
        
        SystemRolePermission::create([
            'action' => 'view',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => 10002
        ]);
    }
}
