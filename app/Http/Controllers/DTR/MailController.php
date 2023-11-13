<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Methods\MailConfig;
use App\Models\EmployeeProfile;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DTR\TwoFactorAuthController;

class MailController extends Controller
{
    private $mail;
    private $twoauth;
    public function __construct()
    {
        $this->mail = new MailConfig();
        $this->twoauth = new TwoFactorAuthController();
    }
    public function sendOTP(Request $request)
    {
        $data = $request->data;
        $employee = EmployeeProfile::where('employee_id', $data['employeeID']);
        $body = view('mail.otp', ['otpcode' => $this->twoauth->Get_OTP($employee)]);
        $data = [
            'Subject' => 'ONE TIME PIN',
            'To_receiver' => $data['To_receiver'],
            'Receiver_Name' => $data['Receiver_Name'],
            'Body' => $body
        ];
        if ($this->mail->Send($data)) {
            return response()->json(['message' => 'Send Successfully!']);
        }
        return response()->json(['message' => 'Messaged Sending Failed!']);
    }
}
