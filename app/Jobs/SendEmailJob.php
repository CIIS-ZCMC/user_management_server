<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Methods\MailConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $email;

    /**
     * Create a new job instance.
     */
    public function __construct($email_type, $email, $name, $data)
    {
        $subject = null;
        $body = null;

        switch ($email_type) {
            case "leave_request":
                $subject = 'ZCMC - UMIS New Leave Request';
                $body = View::make('leave.approving', ['data' => $data])->render();
                break;
            case "overtime_request":
                $subject = 'New Overtime Request Submitted';
                $body = View::make('overtime.approving', ['data' => $data])->render();
                break;
            case "oic_request":
                $subject = 'New OIC assigned';
                $body = View::make('leave.oic', ['data' => $data])->render();
                break;
            case "new_account":
                $subject = 'Your ZCMC Portal Account.';
                $body = View::make('mail.credentials', $data)->render();
                break;
            case "reset_account":
                $subject = 'Your ZCMC Portal Account.';
                $body = View::make('reset_password.recover', $data)->render();
                break;
            case "email_verification":
                $subject = 'Your OTP Email Verification';
                $body = View::make('mail.otp', $data)->render();
                break;
            case "otp":
                $subject = 'ONE TIME PIN';
                $body = View::make('mail.otp', $data)->render();
                break;
        }

        $this->email = [
            'Subject' => $subject,
            'To_receiver' => $email,
            'Receiver_Name' => $name,
            'Body' => $body
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mail = new MailConfig();

        $attempt = 0;
        Log::channel('custom-info')->info("Test");

        while ($attempt < 3) {
            if ($mail->send($this->email)) {
                Helpers::infoLog("SendEmailJob", "handle", "Sent Email");
                return;
            }
            $attempt += 1;
        }

        Helpers::errorLog("SendEmailJob", "handle", "Failed to send email");
    }
}
