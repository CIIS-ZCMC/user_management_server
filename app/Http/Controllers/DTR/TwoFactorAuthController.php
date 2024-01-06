<?php

namespace App\Http\Controllers\DTR;

use App\Models\Contact;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TwoFactorAuthController extends Controller
{
    public function getOTP($employee)
    {
        /**
         * Creates the One time Pin
         * 
         * Validation Included ( checking for expiry ).
         * if expired then we'll renew the code and its expiration
         */
        $otp_code = '';
        $gen_code = rand(100000, 999999);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes')); /* Expires after 5 minutes. */
        $date_now = date('Y-m-d H:i:s');
        $emp = $employee->get();
        if ($emp[0]->otp == null) {
            $employee->update([
                'otp' => $gen_code,
                'otp_expiration' => $otp_expiry
            ]);
            $otp_code = $gen_code;
        } else {
            if ($date_now > $emp[0]->otp_expiration) {
                $employee->update([
                    'otp' => $gen_code,
                    'otp_expiration' => $otp_expiry
                ]);
                $otp_code = $gen_code;
            } else {
                $otp_code = $emp[0]->otp;
            }
        }
        return $otp_code;
    }
    private function isOTPActive($employee)
    {
        $emp = $employee->get();
        $date_now = date('Y-m-d H:i:s');
        $otp_exiry = date('Y-m-d H:i:s', strtotime($emp[0]->otp_expiration));
        if ($date_now > $otp_exiry) {
            //If expired OTP then allow Sending Email
            return false;
        }
        return true;
    }

    private function validateEmployee($employee_ID, $email)
    {
        /**
         * Validating employee
         * First checking its employee profile. 
         * once validated then
         * we check into its contact if email address matches the given to its employee profile
         */
        $employee = EmployeeProfile::where('employee_id', $employee_ID);
        if (count($employee->get()) >= 1) {
            $check_Email = Contact::where('personal_information_id', $employee->first()->GetPersonalInfo()['id'])->where('email_address', $email)->get();
            if (count($check_Email) >= 1) {
                return $employee;
            }
        }
        return false;
    }


    public function eVerification(Request $request)
    {
        /**
         * This function Covers OTP two factor auth for Login and Password Recovery
         * 
         *Validating Employee ID and Email
         * for verification. 
         * if the User is verified as employee. we'll Send the OTP
         */

        try {
            $employee_ID = $request->employeeID;
            $email = $request->email;

            if ($this->validateEmployee($employee_ID, $email)) {

                $employee = $this->validateEmployee($employee_ID, $email);
                $employee_Data = [
                    'To_receiver' => $email,
                    'Receiver_Name' => $employee->first()->name(),
                    'employeeID' => $employee_ID
                ];
                if ($this->isOTPActive($employee)) {
                    return response()->json(['message' => 'Ok']);
                }
                $this->getOTP($employee);
                return redirect()->route('mail.sendOTP', ['data' => $employee_Data])->cookie('access', json_encode(['email' => $email, 'employeeID' => $employee_ID]), 60, '/', env('SESSION_DOMAIN'), true);
            }
            return response()->json(['message' => 'Records not found'], 401);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
    public function verifyOTP(Request $request)
    {
        /**
         * Validating OTP code sent by the user. 
         * We also validate it's employee status
         * Checking if the OTP matches the sent OTP through email
         */
        try {
            $otpCode = $request->otpCode;
            $employee_Data = json_decode($request->cookie('access'));
            if (!isset($employee_Data)) {
                return response()->json(['message' => 'Invalid Request'], 401);
            }
            $email = $employee_Data->email;
            $employee_ID = $employee_Data->employeeID;
            if ($this->validateEmployee($employee_ID, $email)) {
                $employee = $this->validateEmployee($employee_ID, $email);
                if ($this->isOTPActive($employee)) {
                    $activecode  =   $this->getOTP($employee);
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
