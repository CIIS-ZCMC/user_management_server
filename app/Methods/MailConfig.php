<?php

namespace App\Methods;

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
        $this->client_id = Cache::get('google_api_client_id');
        $this->client_secret = Cache::get('google_api_client_secret');
        $this->token = Cache::get('system_email_token');
        $this->sys_email = Cache::get('system_email');
        $this->from_System = Cache::get('system_name');
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
        } catch (Exception $e) {
            return false;
        }
    }
}
