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
        $system_module_umis = SystemModule::where('code', 'UMIS-SM')->first();

        ModulePermission::create([
            'code' => $system_module_umis['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_umis['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_umis['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_umis['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_umis['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_umis['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_umis['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_umis['code'] . ' ' . $permission_import['action'],
            'permission_id' => $permission_import['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_umis['code'] . ' ' . $permission_download['action'],
            'permission_id' => $permission_download['id'],
            'system_module_id' => $system_module_umis['id']
        ]);

        /**
         * Employee Management Registration
         */
        $system_module_employee_registration = SystemModule::where('code', 'UMIS-EM')->first();

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'] . ' ' . $permission_import['action'],
            'permission_id' => $permission_import['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_employee_registration['code'] . ' ' . $permission_download['action'],
            'permission_id' => $permission_download['id'],
            'system_module_id' => $system_module_employee_registration['id']
        ]);

        /**
         * Daily Time Record Management Registration
         */
        $system_module_dtr = SystemModule::where('code', 'UMIS-DTRM')->first();

        ModulePermission::create([
            'code' => $system_module_dtr['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_dtr['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_dtr['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_dtr['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_dtr['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_dtr['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_dtr['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_dtr['id']
        ]);

        /**
         * Leave and Overtime Management Registration
         */
        $system_module_leave_ot = SystemModule::where('code', 'UMIS-LM')->first();

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_leave_ot['code'] . ' ' . $permission_download['action'],
            'permission_id' => $permission_download['id'],
            'system_module_id' => $system_module_leave_ot['id']
        ]);

        /**
         * Schedule Management Registration
         */
        $system_module_schedule = SystemModule::where('code', 'UMIS-ScM')->first();

        ModulePermission::create([
            'code' => $system_module_schedule['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_schedule['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_schedule['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_schedule['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_schedule['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_schedule['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_schedule['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_schedule['code'] . ' ' . $permission_download['action'],
            'permission_id' => $permission_download['id'],
            'system_module_id' => $system_module_schedule['id']
        ]);

        /**
         * Personal Information Management
         */
        $system_module_personal_account_management = SystemModule::where('code', 'UMIS-PAM')->first();

        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_personal_account_management['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_personal_account_management['id']
        ]);

        /**
         * Overtime Management
         */
        $system_module_overtime_management = SystemModule::where('code', 'UMIS-OM')->first();

        ModulePermission::create([
            'code' => $system_module_overtime_management['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_overtime_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_overtime_management['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_overtime_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_overtime_management['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_overtime_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_overtime_management['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_overtime_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_overtime_management['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_overtime_management['id']
        ]);

        /**
         * Official Business
         */
        $system_module_official_business = SystemModule::where('code', 'UMIS-OB')->first();

        ModulePermission::create([
            'code' => $system_module_official_business['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_official_business['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_official_business['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_official_business['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_official_business['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_official_business['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_official_business['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_official_business['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_official_business['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_official_business['id']
        ]);
        ModulePermission::create([
            'code' => $system_module_official_business['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_official_business['id']
        ]);


        /**
         * Official Time
         */
        $system_module_official_time = SystemModule::where('code', 'UMIS-OT')->first();

        ModulePermission::create([
            'code' => $system_module_official_time['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_official_time['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_official_time['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_official_time['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_official_time['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_official_time['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_official_time['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_official_time['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_official_time['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_official_time['id']
        ]);
        ModulePermission::create([
            'code' => $system_module_official_time['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_official_time['id']
        ]);

        /**
         * Compensatory Time
         */
        $system_module_compensatory_time = SystemModule::where('code', 'UMIS-CT')->first();

        ModulePermission::create([
            'code' => $system_module_compensatory_time['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_compensatory_time['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_compensatory_time['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_compensatory_time['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_compensatory_time['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_compensatory_time['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_compensatory_time['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_compensatory_time['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_compensatory_time['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_compensatory_time['id']
        ]);
        ModulePermission::create([
            'code' => $system_module_compensatory_time['code'] . ' ' . $permission_request['action'],
            'permission_id' => $permission_request['id'],
            'system_module_id' => $system_module_compensatory_time['id']
        ]);


        /**
         * Time Shift
         */
        $system_module_time_shift = SystemModule::where('code', 'UMIS-TS')->first();

        ModulePermission::create([
            'code' => $system_module_time_shift['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_time_shift['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_time_shift['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_time_shift['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_time_shift['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_time_shift['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_time_shift['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_time_shift['id']
        ]);

        /**
         * Exchange Schedule Management
         */
        $system_module_exchange_schedule = SystemModule::where('code', 'UMIS-ES')->first();

        ModulePermission::create([
            'code' => $system_module_exchange_schedule['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_exchange_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_exchange_schedule['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_exchange_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_exchange_schedule['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_exchange_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_exchange_schedule['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_exchange_schedule['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_exchange_schedule['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_exchange_schedule['id']
        ]);

        /**
         * Pull Out Management
         */
        $system_module_pull_out_management = SystemModule::where('code', 'UMIS-POM')->first();

        ModulePermission::create([
            'code' => $system_module_pull_out_management['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_pull_out_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_pull_out_management['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_pull_out_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_pull_out_management['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_pull_out_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_pull_out_management['code'] . ' ' . $permission_update['action'],
            'permission_id' => $permission_update['id'],
            'system_module_id' => $system_module_pull_out_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_pull_out_management['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_pull_out_management['id']
        ]);

        /**
         * On Call Management
         */
        $system_module_on_call_management = SystemModule::where('code', 'UMIS-OCM')->first();

        ModulePermission::create([
            'code' => $system_module_on_call_management['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_on_call_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_on_call_management['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_on_call_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_on_call_management['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_on_call_management['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_on_call_management['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_on_call_management['id']
        ]);

        /**
         * Time Adjustment
         */
        $system_module_time_adjustment = SystemModule::where('code', 'UMIS-TA')->first();

        ModulePermission::create([
            'code' => $system_module_time_adjustment['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_time_adjustment['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_time_adjustment['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_time_adjustment['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_time_adjustment['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_time_adjustment['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_time_adjustment['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_time_adjustment['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_time_adjustment['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_time_adjustment['id']
        ]);

        /**
         * Holiday
         */
        $system_module_holiday = SystemModule::where('code', 'UMIS-HOL')->first();

        ModulePermission::create([
            'code' => $system_module_holiday['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_holiday['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_holiday['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_holiday['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_holiday['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_holiday['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_holiday['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_holiday['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_holiday['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_holiday['id']
        ]);

        /**
         * Holiday
         */
        $system_module_monthly_working_hours = SystemModule::where('code', 'UMIS-MWH')->first();

        ModulePermission::create([
            'code' => $system_module_monthly_working_hours['code'] . ' ' . $permission_read_all['action'],
            'permission_id' => $permission_read_all['id'],
            'system_module_id' => $system_module_monthly_working_hours['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_monthly_working_hours['code'] . ' ' . $permission_read['action'],
            'permission_id' => $permission_read['id'],
            'system_module_id' => $system_module_monthly_working_hours['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_monthly_working_hours['code'] . ' ' . $permission_write['action'],
            'permission_id' => $permission_write['id'],
            'system_module_id' => $system_module_monthly_working_hours['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_monthly_working_hours['code'] . ' ' . $permission_approve['action'],
            'permission_id' => $permission_approve['id'],
            'system_module_id' => $system_module_monthly_working_hours['id']
        ]);

        ModulePermission::create([
            'code' => $system_module_monthly_working_hours['code'] . ' ' . $permission_delete['action'],
            'permission_id' => $permission_delete['id'],
            'system_module_id' => $system_module_monthly_working_hours['id']
        ]);
    }
}
