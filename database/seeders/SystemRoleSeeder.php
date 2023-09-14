<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use App\Models\System;
use App\Models\SystemRole;

class SystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $system = System::WHERE("code",  env('SYSTEM_ABBREVIATION'))->first();

        Log::channel('custom-error')->error($system);

        SystemRole::create([
            'uuid' => Str::uuid(),
            'name' => 'Super Admin',
            'description' => 'Super Admin has access rights for the UMIS entirely.',
            'system_id' => $system -> uuid
        ]);
        
        SystemRole::create([
            'uuid' => Str::uuid(),
            'name' => 'Admin',
            'description' => 'Admin has limit rights in creating Admin user and transferring admin righs to other user, it will also be limited to some major module.',
            'system_id' => $system -> uuid
        ]);
        
        SystemRole::create([
            'uuid' => Str::uuid(),
            'name' => 'Staff',
            'description' => 'Staff will have rights in UMIS as viewer base on list of system allowed for it to access.',
            'system_id' => $system -> uuid
        ]);
    }
}
