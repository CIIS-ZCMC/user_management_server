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
    private function isOTP_active($employee)
    {
        $emp = $employee->get();
        $datenow = date('Y-m-d H:i:s');
        $otpexiry = date('Y-m-d H:i:s', strtotime($emp[0]->otp_expiration));


        if ($datenow > $otpexiry) {
            //If expired OTP then allow Sending Email
            return false;
        }
        return true;
    }

    private function ValidateEmployee($employeeID, $email)
    {
        /**
         * Validating employee
         * First checking its employee profile. 
         * once validated then
         * we check into its contact if email address matches the given to its employee profile
         */
        $employee = EmployeeProfile::where('employee_id', $employeeID);
        if (count($employee->get()) >= 1) {
            $checkEmail = Contact::where('personal_information_id', $employee->first()->GetPersonalInfo()['id'])->where('email_address', $email)->get();
            if (count($checkEmail) >= 1) {
                return $employee;
            }
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

            if ($this->ValidateEmployee($employeeID, $email)) {
                $employee = $this->ValidateEmployee($employeeID, $email);
                $employeeData = [
                    'To_receiver' => $email,
                    'Receiver_Name' => $employee->first()->name(),
                    'employeeID' => $employeeID
                ];
                if ($this->isOTP_active($employee)) {
                    return response()->json(['message' => 'Ok']);
                }
                $this->Get_OTP($employee);
                return redirect()->route('mail.sendOTP', ['data' => $employeeData])->cookie('access', json_encode(['email' => $email, 'employeeID' => $employeeID]), 60, '/', env('SESSION_DOMAIN'), true);
            }
            return response()->json(['message' => 'Records not found'], 401);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
    public function Verify_OTP(Request $request)
    {
        /**
         * Validating OTP code sent by the user. 
         * We also validate it's employee status
         * Checking if the OTP matches the sent OTP through email
         */
        try {
            $otpCode = $request->otpCode;
            $employeeData = json_decode($request->cookie('access'));
            if (!isset($employeeData)) {
                return response()->json(['message' => 'Invalid Request'], 401);
            }
            $email = $employeeData->email;
            $employeeID = $employeeData->employeeID;
            if ($this->ValidateEmployee($employeeID, $email)) {
                $employee = $this->ValidateEmployee($employeeID, $email);
                if ($this->isOTP_active($employee)) {
                    $activecode  =   $this->Get_OTP($employee);
                } else {
                    return response()->json(['message' => 'Code Expired'], 401);
                }
                if ($otpCode == $activecode) {
                    return response()->json(['message' => 'OTP code matched'], 200);
                }
            }
            return response()->json(['message' => 'Invalid code'], 401);
        } catch (\Throwable $th) {
            return $th;
        }
    }
}
