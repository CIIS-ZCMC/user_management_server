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
            'name' => 'Write',
            'action' => 'write',            
        ]);
        
        //id 2
        Permission::create([
            'name' => 'Read',
            'action' => 'view',            
        ]);
        
        //id 2
        Permission::create([
            'name' => 'Read All',
            'action' => 'view-all',            
        ]);
        
        //id 3
        Permission::create([
            'name' => 'Edit',
            'action' => 'update',            
        ]);
        
        //id 4
        Permission::create([
            'name' => 'Delete',
            'action' => 'delete',    
        ]);
        
        //id 5
        Permission::create([
            'name' => 'Approve',
            'action' => 'approve',    
        ]);
        
        //id 6
        Permission::create([
            'name' => 'Request',
            'action' => 'request',
        ]);
    }
}
