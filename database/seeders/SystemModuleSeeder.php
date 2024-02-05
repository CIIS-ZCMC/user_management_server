<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\SystemModule;

class SystemModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemModule::create([
            'name' => 'Personal Account Management',
            'code' => 'UMIS-PAM',
            'description' => 'General feature for employees.',
            'system_id' => 1
        ]);

        SystemModule::create([
            'name' => 'Daily Time Record Management',
            'code' => 'UMIS-DTRM',
            'description' => 'Creating personal DTR, downloading, generating report, enroll employee biometric and many more.',
            'system_id' => 1
        ]);

        SystemModule::create([
            'name' => 'Leave Management',
            'code' => 'UMIS-LM',
            'description' => 'Generate personal Over Time report, Request and Approval of Overtime, Monthly Auto Add of leave credits and many more.',
            'system_id' => 1
        ]);
        SystemModule::create([
            'name' => 'Overtime Management',
            'code' => 'UMIS-OM',
            'description' => 'About Overtime',
            'system_id' => 1
        ]);
        SystemModule::create([
            'name' => 'Official Business',
            'code' => 'UMIS-OB',
            'description' => 'About Official Business',
            'system_id' => 1
        ]);

        SystemModule::create([
            'name' => 'Official Time',
            'code' => 'UMIS-OT',
            'description' => 'About Official Time',
            'system_id' => 1
        ]);
        SystemModule::create([
            'name' => 'Compensatory Time',
            'code' => 'UMIS-CT',
            'description' => 'About compensation.',
            'system_id' => 1
        ]);
        SystemModule::create([
            'name' => 'Time Adjustment',
            'code' => 'UMIS-TA',
            'description' => 'Creating and approving of Time Adjustment request',
            'system_id' => 1
        ]);
        SystemModule::create([
            'name' => 'Schedule Management',
            'code' => 'UMIS-ScM',
            'description' => 'Creation and Approve of schedules, Dynamic data for time shifting, apply schedule to employee(s) and many more.',
            'system_id' => 1
        ]);
        SystemModule::create([
            'name' => 'Employee Management',
            'code' => 'UMIS-EM',
            'description' => 'About Employee Data Management, from hiring archiving PDS, assigning Job Position or Plantilla and Assigning Area. including updating employment status Employee Personal Account.',
            'system_id' => 1
        ]);

        SystemModule::create([
            'name' => 'System Management',
            'code' => 'UMIS-SM',
            'description' => 'Module that handle registration, system status and system API Key. including also System Roles & Permission',
            'system_id' => 1
        ]);











        SystemModule::create([
            'name' => 'Time Shift',
            'code' => 'UMIS-TS',
            'description' => 'Time Shift Library',
            'system_id' => 1
        ]);

        SystemModule::create([
            'name' => 'Exchange Schedule',
            'code' => 'UMIS-ES',
            'description' => 'Creating  and approving of Exchange Schdule request',
            'system_id' => 1
        ]);

        SystemModule::create([
            'name' => 'Pull Out Management',
            'code' => 'UMIS-POM',
            'description' => 'Creating  and approving of Pull Out request',
            'system_id' => 1
        ]);



        SystemModule::create([
            'name' => 'On Call Management',
            'code' => 'UMIS-OCM',
            'description' => 'Creating On Call',
            'system_id' => 1
        ]);
    }
}
