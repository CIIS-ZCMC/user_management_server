<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Methods\MailConfig;
use App\Http\Controllers\Controller;

class MailController extends Controller
{
    private $mail;
    public function __construct()
    {
        $this->mail = new MailConfig();
    }

    public function testemail(Request $request)
    {
        $body = view('mail.otp');
        $data = [
            'Subject' => 'Test Email For ZCMC portal',
            'To_receiver' => 'reenjie17@gmail.com',
            'Receiver_Name' => 'testemail',
            'Body' => $body
        ];
        if ($this->mail->Send($data)) {
            return response()->json(['message' => 'Send Successfully!']);
        }
        return response()->json(['message' => 'Messaged Sending Failed!']);
    }
}
