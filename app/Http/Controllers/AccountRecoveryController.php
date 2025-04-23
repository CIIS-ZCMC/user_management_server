<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Jobs\SendEmailJob;
use App\Models\EmployeeProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AccountRecoveryController extends Controller
{
    public function update(Request $request)
    {
        $employee_id = $request->query('employee_id');

        $employee = EmployeeProfile::where('employee_id', $employee_id)->first();

        if(!$employee){
            return response()->json(['message' => "Unrecognized employee ID"], Response::HTTP_NOT_FOUND);
        }
        
        $default_password = Helpers::generatePassword();
        $hashPassword = Hash::make($default_password . config('app.salt_value'));
        $encryptedPassword = Crypt::encryptString($hashPassword);
        $now = Carbon::now();
        $twominutes = $now->addMinutes(2)->toDateTimeString();

        $new_password = [
            'password_encrypted' => $encryptedPassword,
            'password_created_at' => now(),
            'password_expiration_at' => $twominutes
        ];

        $personal_information = $employee->personalInformation;
        $contact = $personal_information->contact;
        

        $data = [
            'EmployeeID' => $employee->employee_id,
            'Password' => $default_password
        ];

        // $employee->update($new_password);
        SendEmailJob::dispatch('reset_account', $contact->email_address, $personal_information->name(), $data);

        return response()->json(['message' => "Successfully send new password to employee."], Response::HTTP_OK);
    }
}
