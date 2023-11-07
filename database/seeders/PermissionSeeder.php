<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //id 1
        Permission::create([
            'name' => 'Creation',
            'description' => 'registration of datas',
            'code' => 'WU1',
            'action' => 'write',            
        ]);
        
        //id 2
        Permission::create([
            'name' => 'Viewing',
            'description' => 'read of datas',
            'code' => 'RU1',
            'action' => 'read',            
        ]);
        
        //id 3
        Permission::create([
            'name' => 'Updating',
            'description' => 'apply change to datas',
            'code' => 'UU1',
            'action' => 'update',            
        ]);
        
        //id 4
        Permission::create([
            'name' => 'Deletion',
            'description' => 'delete of datas',
            'code' => 'DU1',
            'action' => 'delete',    
        ]);
        
        //id 5
        Permission::create([
            'name' => 'Approving',
            'description' => 'approving account',
            'code' => 'AU1',
            'action' => 'approve',    
        ]);
        
        //id 6
        Permission::create([
            'name' => 'Request',
            'description' => 'Rights for request',
            'code' => 'RU2',
            'action' => 'request',    
        ]);
    }
}
