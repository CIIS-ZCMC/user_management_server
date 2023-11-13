<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\Contact;

class TwoFactorAuthController extends Controller
{
    public function Get_OTP($employee)
    {
        /**
         * Creates the One time Pin
         * 
         * Validation Included ( checking for expiry ).
         * if expired then we'll renew the code and its expiration
         */
        $otpcode = '';
        $gencode = rand(100000, 999999);
        $otpexpiry = date('Y-m-d H:i:s', strtotime('+5 minutes')); /* Expires after 5 minutes. */
        $datenow = date('Y-m-d H:i:s');
        $emp = $employee->get();
        if ($emp[0]->otp == null) {
            $employee->update([
                'otp' => $gencode,
                'otp_expiration' => $otpexpiry
            ]);
            $otpcode = $gencode;
        } else {
            if ($datenow > $emp[0]->otp_expiration) {
                $employee->update([
                    'otp' => $gencode,
                    'otp_expiration' => $otpexpiry
                ]);
                $otpcode = $gencode;
            } else {
                $otpcode = $emp[0]->otp;
            }
        }
        return $otpcode;
    }
    private function isEmailed($employee)
    {
        $datenow = date('Y-m-d H:i:s');
        $emp = $employee->get();


        if ($datenow > $emp[0]->otp_expiration) {
            return true;
        }
        return false;
    }
    public function EVerification(Request $request)
    {
        /**
         * This function Covers OTP two factor auth for Login and Password Recovery
         * 
         *Validating Employee ID and Email
         * for verification. 
         * if the User is verified as employee. we'll Send the OTP
         */

        try {
            $employeeID = $request->employeeID;
            $email = $request->email;
            $employee = EmployeeProfile::where('employee_id', $employeeID);
            if (count($employee->get()) >= 1) {
                $checkEmail = Contact::where('personal_information_id', $employee->first()->GetPersonalInfo()['id'])->where('email_address', $email)->get();
                if (count($checkEmail) >= 1) {
                    $employeeData = [
                        'To_receiver' => $email,
                        'Receiver_Name' => $employee->first()->name(),
                        'employeeID' => $employeeID
                    ];


                    if ($this->isEmailed($employee)) {
                        $this->Get_OTP($employee); //Create or Get OTP
                        //  return response()->json(['message' => 'Ok']);
                        return "dontsend";
                    }
                    return "send";
                    // return redirect()->route('mail.sendOTP', ['data' => $employeeData]);
                }
            }
            return response()->json(['message' => 'Records not found'], 401);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
