<?php

namespace App\Methods;

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
    private $sysemail;
    private $fromSystem;
    public function __construct()
    {
        $this->client_id = env('GOOGLE_API_CLIENT_ID');
        $this->client_secret = env('GOOGLE_API_CLIENT_SECRET');
        $this->token = env('SYSTEM_EMAIL_TOKEN');
        $this->sysemail = env('SYSTEM_EMAIL');
        $this->fromSystem = env('SYSTEM_NAME');
        $this->provider = new Google([
            'clientId' => $this->client_id,
            'clientSecret' => $this->client_secret,
        ]);
    }
    public function Send($data)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 465;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPAuth = true;
            $mail->AuthType = 'XOAUTH2';
            $mail->setOAuth(
                new OAuth([
                    'provider' => $this->provider,
                    'clientId' => $this->client_id,
                    'clientSecret' => $this->client_secret,
                    'refreshToken' => $this->token,
                    'userName' => $this->sysemail,
                ])
            );
            $mail->setFrom($this->sysemail, $this->fromSystem);
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
        } catch (Exception $e) {
            return $e;
        }
    }
}
