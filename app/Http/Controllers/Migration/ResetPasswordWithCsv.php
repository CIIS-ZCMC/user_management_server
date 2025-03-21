<?php

namespace App\Http\Controllers\migration;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use App\Imports\EmployeeProfileImport;
use Maatwebsite\Excel\Excel;


class ResetPasswordWithCsv extends Controller
{
    public function getLinkOfEmployeeToResetPassword(Request $request, Excel $excel)
    {
        $request->validate([
            'new_employee_list' => 'required|mimes:xlsx,csv|max:2048',
        ]);

        $file = $request->file('new_employee_list');

        $data = $excel->toArray(new EmployeeProfileImport(), $file);

        $filteredEmployeeIds = [];

        if (!empty($data) && isset($data[0])) {
            foreach (array_slice($data[0], 1) as $row) {
                $employee_need_update_of_credentials = $row[2]?? null;

                if(!empty($employee_need_update_of_credentials)){
                    $employeeId = $row[3] ?? null;
                
                    if (!empty($employeeId)) {
                        $filteredEmployeeIds[] = trim($employeeId);
                    }
                }
            }
        }else{
            return response()->json([
                'message' => 'Please check if there is data exist in the UMIS ID. If does please notify support by contacting the ciiz.zcmc@gmail.com.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $employees = EmployeeProfile::whereIn('employee_id', $filteredEmployeeIds)->get();

        $employeeProfileIds = $employees->pluck('id')->toArray();
        $chunkedEmployeeProfileIds = array_chunk($employeeProfileIds, 10);

        $employee_details = $employees->map(function ($employee) {
            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id
            ];
        })->toArray();


        $baseLink = config('app.server_domain') . "/api/reset-password-with-employee-ids?";
        $urls = [];

        foreach($chunkedEmployeeProfileIds as $employee_profile_ids)
        {
            $queryString = http_build_query(['employee_profile_ids' => $employee_profile_ids]);
            $urls[] = $baseLink . $queryString;
        }

        return response()->json([
            'data' => $employee_details,
            'metadata' => [
                "employee_profile_ids" => $employeeProfileIds,
                "method" => "POST",
                "links" => $urls
            ]
        ],Response::HTTP_OK);
    }

    public function resetAndSendNewCredentialToUsers(Request $request)
    {
        try {
            $employeeIds = $request->query('employee_profile_ids');

            if (!is_array($employeeIds)) {
                $employeeIds = explode(',', $employeeIds);
            }

            $employees = EmployeeProfile::whereIn('id', $employeeIds)->get();

            $temp = [];
            $failed_emails = [];

            foreach ($employees as $employee) {
                $password = Helpers::generatePassword();
                $hashPassword = Hash::make($password . config('app.salt_value'));

                $employee->authorization_pin = null;
                $employee->password_encrypted = Crypt::encryptString($hashPassword);
                $employee->save();
                
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
            // dd($th);
            return response()->json([
                'message' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}