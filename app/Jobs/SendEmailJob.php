<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Methods\MailConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $email;

    /**
     * Create a new job instance.
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mail = new MailConfig();

        if (!$mail->send($this->email)) {
            Helpers::errorLog("SendEmailJob", "handle", "Failed to send email");
        }

        Helpers::infoLog("SendEmailJob", "handle", "Sent Email");
    }
}
