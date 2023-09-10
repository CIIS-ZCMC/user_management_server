<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\SystemRole;

class SystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemRole::create([
            'name' => 'Super Admin',
            'description' => 'Super Admin has access rights for the UMIS entirely.',
            'system_id' => 10000
        ]);
        
        SystemRole::create([
            'name' => 'Admin',
            'description' => 'Admin has limit rights in creating Admin user and transferring admin righs to other user, it will also be limited to some major module.',
            'system_id' => 10000
        ]);
        
        SystemRole::create([
            'name' => 'Staff',
            'description' => 'Staff will have rights in UMIS as viewer base on list of system allowed for it to access.',
            'system_id' => 10000
        ]);
    }
}
