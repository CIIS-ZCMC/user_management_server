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
use Symfony\Component\HttpFoundation\Response;

use function Laravel\Prompts\select;

class ResetPasswordWithCsv extends Controller
{
    public function resetAndSendNewCredentialToUsers(Request $request)
    {
        try {
            $employeeIds = $request->input('employee_ids'); // Laravel automatically handles JSON
            $employees = EmployeeProfile::whereIn('id', $employeeIds)->get();

            $temp = [];
            $failed_emails = [];


            foreach ($employees as $employee) {
                $password = Helpers::generatePassword();
                $hashPassword = Hash::make($password . config('app.salt_value'));
                

                $employee->authorization_pin = null;
                $employee->password_encrypted = Crypt::encryptString($hashPassword);
                $employee->save();
                // $default_password = Helpers::generatePassword();
                $data = [
                    'employeeID' => $employee->employee_id,
                    'Password' => $password,
                    "Link" => config('app.client_domain')
                ];

                if ($employee->personalinformation->contact == null) {
                    continue;
                }
                
                $email = $employee->personalinformation->contact->email_address;

                if ($email == 'd@gmail.com' || $email === null) {
                    $failed_emails[] = [
                        "employee_id" => $employee->employee_id,
                        "email" => $email
                    ];
                    continue;
                }

                $name = $employee->personalInformation->name();

                SendEmailJob::dispatch('new_account', $email, $name, $data);
                $temp[] = ['sent' => $email];
                \Log::info('RESET', [
                    'employeeID' => $employee->employee_id,
                    'password' => $password,
                ]);
            }

            if(count($failed_emails) > 0){
                return response()->json([
                    'data' => $temp,
                    "failed_emails" => $failed_emails,
                    "message" => "Successfully send new account credentials to selected employees. Some email failed to send with reason of email is doesn't exist."
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => $temp,
                "message" => "Successfully send new account credentials to selected employees."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}