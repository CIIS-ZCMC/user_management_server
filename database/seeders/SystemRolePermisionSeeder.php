<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\SystemRolePermission;
use App\Models\SystemRole;

class SystemRolePermisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $system_role = SystemRole::where('name', 'Super Admin')->first();

        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'create',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'view',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);

        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'put',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'delete',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'create',
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'view',
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);

        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'put',
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'delete',
            'module' => 'employee',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        $system_role = SystemRole::where('name', 'Admin')->first();

        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'create',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'view',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'put',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'delete',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
        
        $system_role = SystemRole::where('name', 'Staff')->first();

        SystemRolePermission::create([
            'uuid' => Str::uuid(),
            'action' => 'view',
            'module' => 'user',
            'active' => TRUE,
            'system_role_id' => $system_role -> uuid
        ]);
    }
}
