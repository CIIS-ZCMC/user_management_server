<?php

namespace App\Http\Controllers\migration;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\select;

class ResetPasswordWithCsv extends Controller
{
    public function resetAndSendNewCredentialToUsers()
    {
        $emm = null;
        try {
            // dd(EmployeeProfile::all());
            $employees = EmployeeProfile::all();
            // $employees = EmployeeProfile::offset(450)->limit(100)->get();
            $temp = [];
            $employeeProfiles = EmployeeProfile::leftJoin('assigned_areas as aa', 'employee_profiles.id', '=', 'aa.employee_profile_id')
                ->select('employee_profiles.employee_id', 'aa.*')
                ->where(function ($query) {
                    $query->where('aa.section_id', 1);
                })
                ->get()->pluck('employee_profile_id');


            foreach ($employees as $employee) {
                $password = Helpers::generatePassword();
                $hashPassword = Hash::make($password . config('app.salt_value'));

                // $temp[] = ['id' => $employee->employee_id, 'pass' => $password];
                if (in_array($employee->id, [2663])) {

                    $employee->authorization_pin = null;
                    $employee->password_encrypted = Crypt::encryptString($hashPassword);
                    $employee->save();
                    $employee_profile = EmployeeProfile::find($employee->id);
                    // $default_password = Helpers::generatePassword();
                    $data = [
                        'employeeID' => $employee_profile->employee_id,
                        'Password' => $password,
                        "Link" => config('app.client_domain')
                    ];
                    if ($employee_profile->personalinformation->contact == null) {
                        continue;
                    }
                    $email = $employee_profile->personalinformation->contact->email_address;
                    if ($email == 'd@gmail.com') {
                        continue;
                    }

                    $name = $employee_profile->personalInformation->name();


                    SendEmailJob::dispatch('new_account', $email, $name, $data);
                    $temp[] = ['sent' => $email];
                    \Log::info('RESET', [
                        'employeeID' => $employee->employee_id,
                        'password' => $password,
                    ]);
                }
            }

            \Log::info('P-Reset&sent Successfully', ["message" => '-------------------------------------------------------------------------------------------']);
            // dd($temp);
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }
}