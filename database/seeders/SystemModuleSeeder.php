<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemModule::create([
            'name' => 'System Management',
            'code' => 'UMIS-SM',
            'description' => 'Module that handle registration, system status and system API Key. including also System Roles & Permission',
            'system_id' => 1
        ]);

        SystemModule::create([
            'name' => 'Employee Management',
            'code' => 'UMIS-EM',
            'description' => 'About Employee Data Management, from hiring archiving PDS, assigning Job Position or Plantilla and Assigning Area. including updating employment status Employee Personal Account.',
            'system_id' => 1
        ]);

        SystemModule::create([
            'name' => 'Daily Time Record Management',
            'code' => 'UMIS-DTRM',
            'description' => 'Creating personal DTR, downloading, generating report, enroll employee biometric and many more.',
            'system_id' => 1
        ]);
        
        SystemModule::create([
            'name' => 'Leave and Overtime Management',
            'code' => 'UMIS-LOM',
            'description' => 'Generate personal Over Time report, Request and Approval of Overtime, Monthly Auto Add of leave credits and many more.',
            'system_id' => 1
        ]);
        
        SystemModule::create([
            'name' => 'Schedule Management',
            'code' => 'UMIS-ScM',
            'description' => 'Creation and Approve of schedules, Dynamic data for time shifting, apply schedule to employee(s) and many more.',
            'system_id' => 1
        ]);
    }
}
