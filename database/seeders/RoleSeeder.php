<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'Super Admin',
            'code' => 'super_admin'
        ]);
        
        Role::create([
            'name' => 'Admin I',
            'code' => 'admin_001'
        ]);
        
        Role::create([
            'name' => 'Admin II',
            'code' => 'admin_002'
        ]);
        
        Role::create([
            'name' => 'Admin III',
            'code' => 'admin_003'
        ]);
    }
}
