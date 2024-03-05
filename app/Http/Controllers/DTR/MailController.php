<?php

namespace App\Http\Controllers\DTR;

use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use App\Methods\MailConfig;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DTR\TwoFactorAuthController;

class MailController extends Controller
{
    private $mail;
    private $two_auth;
    public function __construct()
    {
        $this->mail = new MailConfig();
        $this->two_auth = new TwoFactorAuthController();
    }
    public function sendOTP(Request $request)
    {
        $data = $request->data;
        $employee = EmployeeProfile::where('employee_id', $data['employeeID']);
        $body = view('mail.otp', ['otpcode' => $this->two_auth->getOTP($employee)]);
        $data = [
            'Subject' => 'ONE TIME PIN',
            'To_receiver' => $data['To_receiver'],
            'Receiver_Name' => $data['Receiver_Name'],
            'Body' => $body
        ];
        if ($this->mail->send($data)) {
            return response()->json(['message' => 'Send Successfully!']);
        }
        return response()->json(['message' => 'Messaged Sending Failed!']);
    }


    public function sendCredentials(Request $request)
    {

        $body = view('mail.credentials', ['employeeID' => $request->EmployeeID, 'Password' => $request->Password, 'Link' => env('CLIENT_DOMAIN'), "authorization_pin" => $request->authorization_pin]);
        $data = [
            'Subject' => 'Greetings from ZCMC-Portal-Team',
            'To_receiver' =>  $request->Email,
            'Receiver_Name' => $request->Receiver,
            'Body' => $body
        ];
        $this->mail->send($data);
    }
}
