<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailSender
{
    private static $host = 'mail.eteamid.com';
    private static $username = 'sarfraz@eteamid.com';
    private static $password = '@}24v94ztB2{';
    private static $port = 465;
    private static $secure = 'ssl'; // Use 'tls' if required

    public static function sendEmail(string $toEmail, string $toName, string $subject, string $body, array $ccs = [])
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = static::$host;
            $mail->SMTPAuth = true;
            $mail->Username = static::$username;
            $mail->Password = static::$password;
            $mail->SMTPSecure = static::$secure;
            $mail->Port = static::$port;

            // Recipients
            $mail->setFrom(static::$username, 'Mr X');
            $mail->addAddress($toEmail, $toName);

            foreach ($ccs as $cc) {
                $mail->addCC($cc);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            logMessage('Email could not be sent. Mailer Error: ' . $mail->ErrorInfo, 'error');
            return false;
        }
    }
}