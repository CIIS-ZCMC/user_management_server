<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\SystemModule;
use App\Models\Permission;
use App\Models\ModulePermission;

class ModulePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission_read_all = Permission::where('action', 'view-all')->first();
        $permission_write = Permission::where('action', 'write')->first();
        $permission_read = Permission::where('action', 'view')->first();
        $permission_update = Permission::where('action', 'update')->first();
        $permission_delete = Permission::where('action', 'delete')->first();
        $permission_approve = Permission::where('action', 'approve')->first();
        $permission_request = Permission::where('action', 'request')->first();
        $permission_import = Permission::where('action', 'import')->first();
        $permission_download = Permission::where('action', 'download')->first();

        /**
         * Umis Module Registration
         */
        $system_module_umis = SystemModule::find(1);

        ModulePermission::create([
            'code' => $system_module_umis['code'].' '.$permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_umis['code'].' '.$permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_umis['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_umis['code'].' '.$permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_umis['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_umis['code'].' '.$permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_umis['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_umis['code'].' '.$permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_umis['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_umis['code'].' '.$permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_umis['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_umis['code'].' '.$permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_umis['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_umis['code'].' '.$permission_import['action'],
            'permission_id' => $permission_import['id'],
            'system_module_id' => $system_module_umis['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_umis['code'].' '.$permission_download['action'],
            'permission_id' => $permission_download['id'],
            'system_module_id' => $system_module_umis['id']
        ]);
        
        /**
         * Employee Management Registration
         */
        $system_module_employee_registration = SystemModule::find(2);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'].' '.$permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'].' '.$permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_employee_registration['code'].' '.$permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_employee_registration['code'].' '.$permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_employee_registration['code'].' '.$permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_employee_registration['code'].' '.$permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_employee_registration['code'].' '.$permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_employee_registration['code'].' '.$permission_import['action'],
            'permission_id' => $permission_import['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_employee_registration['code'].' '.$permission_download['action'],
            'permission_id' => $permission_download['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);
        
        /**
         * Daily Time Record Management Registration
         */
        $system_module_dtr = SystemModule::find(3);

        ModulePermission::create([
            'code' => $system_module_dtr['code'].' '.$permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_dtr['code'].' '.$permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_dtr['code'].' '.$permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_dtr['code'].' '.$permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_dtr['code'].' '.$permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_dtr['code'].' '.$permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_dtr['code'].' '.$permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);
        
        /**
         * Leave and Overtime Management Registration
         */
        $system_module_leave_ot = SystemModule::find(4);

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'].' '.$permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'].' '.$permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_leave_ot['code'].' '.$permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_leave_ot['code'].' '.$permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_leave_ot['code'].' '.$permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_leave_ot['code'].' '.$permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_leave_ot['code'].' '.$permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);
        
        /**
         * Schedule Management Registration
         */
        $system_module_schedule = SystemModule::find(5);

        ModulePermission::create([
            'code' => $system_module_schedule['code'].' '.$permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_schedule['code'].' '.$permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_schedule['code'].' '.$permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_schedule['code'].' '.$permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_schedule['code'].' '.$permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_schedule['code'].' '.$permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_schedule['code'].' '.$permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);
        
        /**
         * Personal Information Management
         */
        $system_module_personal_account_management = SystemModule::find(6);

        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'].' '.$permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'].' '.$permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'].' '.$permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'].' '.$permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);
        
        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'].' '.$permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);
    }
}
