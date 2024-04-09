<?php

namespace App\Methods;

use App\Helpers\Helpers;
use Illuminate\Support\Facades\Cache;
use League\OAuth2\Client\Provider\Google;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class MailConfig
{
    private $client_id;
    private $client_secret;
    private $token;
    private $provider;
    private $sys_email;
    private $from_System;

    public function __construct()
    {
        $this->client_id = "595317458897-8kio3rsqktn70cuomcpev8cqau2sl0oi.apps.googleusercontent.com";
        $this->client_secret = "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";
        $this->token = "1//0gv1dy3TRxnqMCgYIARAAGBASNwF-L9IrgPQTTi8NYfel7ENvwhtw8S3cBAMKGIvblfe9fbE8E29EdemDBJYakiFMBDR4PL_lW8c";
        $this->sys_email = "ciis.zcmc@gmail.com";
        $this->from_System = "ZCMC Portal";
        $this->provider = new Google([
            'clientId' => $this->client_id,
            'clientSecret' => $this->client_secret,
        ]);
    }
    public function send($data)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 465;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            // $mail->SMTPAutoTLS = false;
            $mail->SMTPAuth = true;
            $mail->AuthType = 'XOAUTH2';
            $mail->setOAuth(
                new OAuth([
                    'provider' => $this->provider,
                    'clientId' => $this->client_id,
                    'clientSecret' => $this->client_secret,
                    'refreshToken' => $this->token,
                    'userName' => $this->sys_email,
                ])
            );
            $mail->setFrom($this->sys_email, $this->from_System);
            $mail->addAddress($data['To_receiver'], $data['Receiver_Name']);
            $mail->Subject = $data['Subject'];
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->isHTML(true);
            $mail->Body = $data['Body'];
            $mail->AltBody = 'This is a plain text message body';
            if ($mail->send()) {
                return true;
            } else {
                return false;
            }
        } catch (\Throwable $th) {
            Helpers::errorLog("MailConfig", "send", $th->getMessage());

            return false;
        }
    }
}
