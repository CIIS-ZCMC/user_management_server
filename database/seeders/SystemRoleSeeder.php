<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

use App\Models\System;
use App\Models\SystemRole;
use App\Models\ModulePermission;
use App\Models\RoleModulePermission;

class SystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $system = System::WHERE("code",  Cache::get('system_abbreviation'))->first();

        // $role = Role::where('code', "SUPER-USER-00")->first();

        // $super_admin = SystemRole::create([
        //     'role_id' => $role->id,
        //     'system_id' => $system->id
        // ]);

        // $module_permissions = ModulePermission::all();

        // foreach ($module_permissions as $key => $module_permission) {
        //     RoleModulePermission::create([
        //         'system_role_id' => $super_admin['id'],
        //         'module_permission_id' => $module_permission['id']
        //     ]);
        // }

        $module_permissions = ModulePermission::all();


        $role = Role::all();
        foreach ($role as $key => $roles) {

            switch ($roles->code) {
                case 'SUPER-USER-00':
                    $super_Admin =  SystemRole::create([
                        'role_id' => $roles->id,
                        'system_id' => $system->id
                    ]);

                    foreach ($module_permissions as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $super_Admin['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }
                    break;

                case 'OMCC-01':
                    $omcc = SystemRole::create([
                        'role_id' =>  $roles->id,
                        'system_id' => $system->id
                    ]);
                    $module_permitted =  $module_permissions->filter(function ($row) {
                        return
                            /* DTR */
                            $row['code'] === "UMIS-DTRM view-all" ||

                            /* Leave Management */
                            $row['code'] === "UMIS-LM view-all" ||
                            $row['code'] === "UMIS-LM approve" ||

                            /* Overtime Management */
                            $row['code'] === "UMIS-OM view-all" ||
                            $row['code'] === "UMIS-OM approve" ||

                            /* Official Business */
                            $row['code'] === "UMIS-OB view-all" ||
                            $row['code'] === "UMIS-OB approve" ||

                            /* Official Time */
                            $row['code'] === "UMIS-OT view-all" ||
                            $row['code'] === "UMIS-OT approve" ||

                            /* Compensantory Time */
                            $row['code'] === "UMIS-CT view-all" ||
                            $row['code'] === "UMIS-CT approve" ||
                            $row['code'] === "UMIS-CT request" ||

                            /* Time adjustment  */
                            $row['code'] === "UMIS-TA view-all" ||
                            $row['code'] === "UMIS-TA approve" ||

                            /* Schedule management */
                            $row['code'] === "UMIS-ScM write" ||
                            $row['code'] === "UMIS-ScM view-all" ||
                            $row['code'] === "UMIS-ScM update" ||
                            $row['code'] === "UMIS-ScM delete" ||
                            $row['code'] === "UMIS-ScM approve" ||
                            $row['code'] === "UMIS-ScM download" ||


                            /* Employee management */
                            $row['code'] === "UMIS-EM view" ||
                            $row['code'] === "UMIS-EM view-all" ||
                            $row['code'] === "UMIS-EM update" ||
                            $row['code'] === "UMIS-EM delete" ||
                            $row['code'] === "UMIS-EM import" ||
                            $row['code'] === "UMIS-EM download";
                    });
                    foreach ($module_permitted as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $omcc['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }

                    break;

                case 'HRMO-HEAD-01':
                    $hrmo = SystemRole::create([
                        'role_id' =>  $roles->id,
                        'system_id' => $system->id
                    ]);
                    $module_permitted =  $module_permissions->filter(function ($row) {
                        return
                            /* DTR */
                            $row['code'] === "UMIS-DTRM view-all" ||
                            /* Leave Management */
                            $row['code'] === "UMIS-LM write" ||
                            $row['code'] === "UMIS-LM view-all" ||
                            $row['code'] === "UMIS-LM approve" ||

                            /* Overtime Management */
                            $row['code'] === "UMIS-OM write" ||
                            $row['code'] === "UMIS-OM view-all" ||
                            $row['code'] === "UMIS-OM approve" ||

                            /* Official Business */
                            $row['code'] === "UMIS-OB write" ||
                            $row['code'] === "UMIS-OB view-all" ||
                            $row['code'] === "UMIS-OB approve" ||

                            /* Official Time */
                            $row['code'] === "UMIS-OT write" ||
                            $row['code'] === "UMIS-OT view-all" ||
                            $row['code'] === "UMIS-OT approve" ||
                            $row['code'] === "UMIS-CT request" ||

                            /* Compensantory Time */
                            $row['code'] === "UMIS-CT write" ||
                            $row['code'] === "UMIS-CT view-all" ||
                            $row['code'] === "UMIS-CT approve" ||

                            /* Time adjustment  */
                            $row['code'] === "UMIS-TA write" ||
                            $row['code'] === "UMIS-TA view-all" ||
                            $row['code'] === "UMIS-TA approve" ||

                            /* Schedule management */
                            $row['code'] === "UMIS-ScM write" ||
                            $row['code'] === "UMIS-ScM view-all" ||
                            $row['code'] === "UMIS-ScM update" ||
                            $row['code'] === "UMIS-ScM delete" ||
                            $row['code'] === "UMIS-ScM approve" ||
                            $row['code'] === "UMIS-ScM download" ||

                            /* Employee management */
                            $row['code'] === "UMIS-EM write" ||
                            $row['code'] === "UMIS-EM view" ||
                            $row['code'] === "UMIS-EM view-all" ||
                            $row['code'] === "UMIS-EM update" ||
                            $row['code'] === "UMIS-EM delete" ||
                            $row['code'] === "UMIS-EM approve" ||
                            $row['code'] === "UMIS-EM request" ||
                            $row['code'] === "UMIS-EM import" ||
                            $row['code'] === "UMIS-EM download" ||

                            /* Employee management */

                            $row['code'] === "UMIS-SM view-all";
                    });
                    foreach ($module_permitted as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $hrmo['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }

                    break;

                case 'HR-ADMIN':
                    $hr_admin = SystemRole::create([
                        'role_id' =>  $roles->id,
                        'system_id' => $system->id
                    ]);
                    $module_permitted =  $module_permissions->filter(function ($row) {
                        return
                            /* DTR */
                            $row['code'] === "UMIS-DTRM view-all" ||
                            /* Leave Management */
                            $row['code'] === "UMIS-LM write" ||
                            $row['code'] === "UMIS-LM view-all" ||
                            $row['code'] === "UMIS-LM approve" ||

                            /* Overtime Management */
                            $row['code'] === "UMIS-OM write" ||
                            $row['code'] === "UMIS-OM view-all" ||

                            /* Official Business */
                            $row['code'] === "UMIS-OB write" ||
                            $row['code'] === "UMIS-OB view-all" ||
                            $row['code'] === "UMIS-OB approve" ||

                            /* Official Time */
                            $row['code'] === "UMIS-OT write" ||
                            $row['code'] === "UMIS-OT view-all" ||


                            /* Compensantory Time */
                            $row['code'] === "UMIS-CT write" ||
                            $row['code'] === "UMIS-CT view-all" ||

                            /* Time adjustment  */
                            $row['code'] === "UMIS-TA write" ||
                            $row['code'] === "UMIS-TA view-all" ||

                            /* Schedule management */
                            $row['code'] === "UMIS-ScM write" ||
                            $row['code'] === "UMIS-ScM view-all" ||
                            $row['code'] === "UMIS-ScM update" ||
                            $row['code'] === "UMIS-ScM delete" ||
                            $row['code'] === "UMIS-ScM approve" ||
                            $row['code'] === "UMIS-ScM download" ||

                            /* Employee management */
                            $row['code'] === "UMIS-EM write" ||
                            $row['code'] === "UMIS-EM view-all" ||
                            $row['code'] === "UMIS-EM update" ||
                            $row['code'] === "UMIS-EM delete" ||
                            $row['code'] === "UMIS-EM approve" ||
                            $row['code'] === "UMIS-EM download";
                    });
                    foreach ($module_permitted as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $hr_admin['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }
                    break;

                case 'DIV-HEAD-01':
                    $div_head3 = SystemRole::create([
                        'role_id' =>  $roles->id,
                        'system_id' => $system->id
                    ]);
                    $module_permitted =  $module_permissions->filter(function ($row) {
                        return
                            /* DTR */
                            $row['code'] === "UMIS-DTRM view-all" ||
                            /* Leave Management */

                            $row['code'] === "UMIS-LM view-all" ||
                            $row['code'] === "UMIS-LM approve" ||

                            /* Overtime Management */

                            $row['code'] === "UMIS-OM view-all" ||
                            $row['code'] === "UMIS-OM approve" ||

                            /* Official Business */

                            $row['code'] === "UMIS-OB view-all" ||
                            $row['code'] === "UMIS-OB approve" ||

                            /* Official Time */

                            $row['code'] === "UMIS-OT view-all" ||
                            $row['code'] === "UMIS-OT approve" ||
                            $row['code'] === "UMIS-CT request" ||

                            /* Compensantory Time */

                            $row['code'] === "UMIS-CT view-all" ||
                            $row['code'] === "UMIS-CT approve" ||
                            
                            $row['code'] === "UMIS-ES view" ||
                            $row['code'] === "UMIS-ES view-all" ||
                            $row['code'] === "UMIS-ES approve" ||

                            /* Time adjustment  */

                            $row['code'] === "UMIS-TA view-all" ||
                            $row['code'] === "UMIS-TA approve" ||

                            /* Schedule management */
                            $row['code'] === "UMIS-ScM write" ||
                            $row['code'] === "UMIS-ScM view-all" ||
                            $row['code'] === "UMIS-ScM update" ||
                            $row['code'] === "UMIS-ScM delete" ||
                            $row['code'] === "UMIS-ScM approve" ||
                            $row['code'] === "UMIS-ScM download" ||

                            /* Employee management */

                            $row['code'] === "UMIS-EM view-all";

                        /* Employee management */
                    });
                    foreach ($module_permitted as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $div_head3['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }

                    break;

                case 'DEPT-HEAD-01':

                    $dept_head4 = SystemRole::create([
                        'role_id' =>  $roles->id,
                        'system_id' => $system->id
                    ]);
                    $module_permitted =  $module_permissions->filter(function ($row) {
                        return
                            /* DTR */
                            $row['code'] === "UMIS-DTRM view-all" ||
                            /* Leave Management */

                            $row['code'] === "UMIS-LM view-all" ||
                            $row['code'] === "UMIS-LM approve" ||

                            /* Overtime Management */

                            $row['code'] === "UMIS-OM view-all" ||
                            $row['code'] === "UMIS-OM approve" ||

                            /* Official Business */

                            $row['code'] === "UMIS-OB view-all" ||
                            $row['code'] === "UMIS-OB approve" ||

                            /* Official Time */

                            $row['code'] === "UMIS-OT view-all" ||
                            $row['code'] === "UMIS-OT approve" ||
                            $row['code'] === "UMIS-CT request" ||

                            /* Compensantory Time */

                            $row['code'] === "UMIS-CT view-all" ||
                            $row['code'] === "UMIS-CT approve" ||

                            /* Time adjustment  */

                            $row['code'] === "UMIS-TA view-all" ||
                            $row['code'] === "UMIS-TA approve" ||
                            
                            $row['code'] === "UMIS-ES view" ||
                            $row['code'] === "UMIS-ES view-all" ||
                            $row['code'] === "UMIS-ES approve" ||

                            /* Schedule management */
                            $row['code'] === "UMIS-ScM write" ||
                            $row['code'] === "UMIS-ScM view-all" ||
                            $row['code'] === "UMIS-ScM update" ||
                            $row['code'] === "UMIS-ScM delete" ||
                            $row['code'] === "UMIS-ScM approve" ||
                            $row['code'] === "UMIS-ScM download" ||

                            /* Employee management */

                            $row['code'] === "UMIS-EM view-all";

                        /* Employee management */
                    });
                    foreach ($module_permitted as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $dept_head4['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }


                    break;

                case 'SECTION-HEAD-01':

                    $sec_head5 = SystemRole::create([
                        'role_id' =>  $roles->id,
                        'system_id' => $system->id
                    ]);
                    $module_permitted =  $module_permissions->filter(function ($row) {
                        return
                            /* DTR */
                            $row['code'] === "UMIS-DTRM view-all" ||
                            /* Leave Management */

                            $row['code'] === "UMIS-LM view-all" ||
                            $row['code'] === "UMIS-LM approve" ||

                            /* Overtime Management */

                            $row['code'] === "UMIS-OM view-all" ||
                            $row['code'] === "UMIS-OM approve" ||

                            /* Official Business */

                            $row['code'] === "UMIS-OB view-all" ||
                            $row['code'] === "UMIS-OB approve" ||
                            $row['code'] === "UMIS-CT request" ||

                            /* Official Time */

                            $row['code'] === "UMIS-OT view-all" ||
                            $row['code'] === "UMIS-OT approve" ||

                            /* Compensantory Time */

                            $row['code'] === "UMIS-CT view-all" ||
                            $row['code'] === "UMIS-CT approve" ||

                            /* Time adjustment  */

                            $row['code'] === "UMIS-TA view-all" ||
                            $row['code'] === "UMIS-TA approve" ||
                            
                            $row['code'] === "UMIS-ES view" ||
                            $row['code'] === "UMIS-ES view-all" ||
                            $row['code'] === "UMIS-ES approve" ||

                            /* Schedule management */
                            $row['code'] === "UMIS-ScM write" ||
                            $row['code'] === "UMIS-ScM view-all" ||
                            $row['code'] === "UMIS-ScM update" ||
                            $row['code'] === "UMIS-ScM delete" ||
                            $row['code'] === "UMIS-ScM approve" ||
                            $row['code'] === "UMIS-ScM download"  ||

                            /* Employee management */

                            $row['code'] === "UMIS-EM view-all";

                        /* Employee management */
                    });
                    foreach ($module_permitted as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $sec_head5['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }


                    break;


                case 'UNIT-HEAD-01':

                    $unit_head6 = SystemRole::create([
                        'role_id' =>  $roles->id,
                        'system_id' => $system->id
                    ]);
                    $module_permitted =  $module_permissions->filter(function ($row) {
                        return
                            /* DTR */
                            $row['code'] === "UMIS-DTRM view-all" ||
                            /* Leave Management */

                            $row['code'] === "UMIS-LM view-all" ||
                            $row['code'] === "UMIS-LM approve" ||

                            /* Overtime Management */

                            $row['code'] === "UMIS-OM view-all" ||
                            $row['code'] === "UMIS-OM approve" ||

                            /* Official Business */

                            $row['code'] === "UMIS-OB view-all" ||
                            $row['code'] === "UMIS-OB approve" ||

                            /* Official Time */

                            $row['code'] === "UMIS-OT view-all" ||
                            $row['code'] === "UMIS-OT approve" ||
                            $row['code'] === "UMIS-CT request" ||

                            /* Compensantory Time */

                            $row['code'] === "UMIS-CT view-all" ||
                            $row['code'] === "UMIS-CT approve" ||
                            
                            $row['code'] === "UMIS-ES view" ||
                            $row['code'] === "UMIS-ES view-all" ||
                            $row['code'] === "UMIS-ES approve" ||

                            /* Time adjustment  */


                            /* Schedule management */
                            $row['code'] === "UMIS-ScM write" ||
                            $row['code'] === "UMIS-ScM view-all" ||
                            $row['code'] === "UMIS-ScM update" ||
                            $row['code'] === "UMIS-ScM delete" ||
                            $row['code'] === "UMIS-ScM approve" ||
                            $row['code'] === "UMIS-ScM download" ||

                            /* Employee management */

                            $row['code'] === "UMIS-EM view-all";

                        /* Employee management */
                    });
                    foreach ($module_permitted as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $unit_head6['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }
                    break;

                case 'COMMON-REG':
                    $common_reg = SystemRole::create([
                        'role_id' =>  $roles->id,
                        'system_id' => $system->id
                    ]);
                    $module_permitted =  $module_permissions->filter(function ($row) {
                        return
                            /* Personal Account Management */
                            $row['code'] === "UMIS-PAM view" ||
                            $row['code'] === "UMIS-PAM request" ||
                            $row['code'] === "UMIS-PAM download" ||
                            /* DTR */
                            $row['code'] === "UMIS-DTRM view" ||
                            $row['code'] === "UMIS-DTRM download" ||
                            /* Leave Management */
                            $row['code'] === "UMIS-LM view" ||
                            $row['code'] === "UMIS-LM request" ||
                            $row['code'] === "UMIS-LM download" ||

                            /* Overtime Management */
                            $row['code'] === "UMIS-OM view" ||
                            // $row['code'] === "UMIS-OM request" ||
                            $row['code'] === "UMIS-OM download" ||

                            /* Official Business */
                            $row['code'] === "UMIS-OB view" ||
                            $row['code'] === "UMIS-OB request" ||
                            $row['code'] === "UMIS-OB download" ||

                            /* Official Time */
                            $row['code'] === "UMIS-OT view" ||
                            $row['code'] === "UMIS-OT request" ||
                            $row['code'] === "UMIS-OT download" ||

                            /* Compensantory Time */
                            $row['code'] === "UMIS-CT view" ||
                            $row['code'] === "UMIS-CT request" ||
                            $row['code'] === "UMIS-CT download" ||

                            /* Time adjustment  */
                            $row['code'] === "UMIS-TA view" ||
                            $row['code'] === "UMIS-TA request" ||
                            $row['code'] === "UMIS-TA download" ||

                            /* Schedule management */
                            $row['code'] === "UMIS-ScM view" ||
                            $row['code'] === "UMIS-ScM request" ||
                            $row['code'] === "UMIS-ScM download";
                    });
                    foreach ($module_permitted as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $common_reg['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }
                    break;

                case 'COMMON-JO':
                    $common_jo = SystemRole::create([
                        'role_id' =>  $roles->id,
                        'system_id' => $system->id
                    ]);
                    $module_permitted =  $module_permissions->filter(function ($row) {
                        return
                            /* Personal Account Management */
                            $row['code'] === "UMIS-PAM view" ||
                            $row['code'] === "UMIS-PAM request" ||
                            $row['code'] === "UMIS-PAM download" ||
                            /* DTR */
                            $row['code'] === "UMIS-DTRM view" ||
                            $row['code'] === "UMIS-DTRM download" ||
                            /* Leave Management */


                            /* Overtime Management */

                            /* Official Business */

                            /* Official Time */

                            /* Compensantory Time */

                            /* Time adjustment  */

                            /* Schedule management */
                            $row['code'] === "UMIS-ScM view" ||
                            $row['code'] === "UMIS-ScM request" ||
                            $row['code'] === "UMIS-ScM download";
                    });
                    foreach ($module_permitted as $key => $module_permission) {
                        RoleModulePermission::create([
                            'system_role_id' => $common_jo['id'],
                            'module_permission_id' => $module_permission['id']
                        ]);
                    }

                    break;
            }
        }
    }
}
