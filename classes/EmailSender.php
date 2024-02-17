<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailSender
{
    private static string $host = 'mail.eteamid.com';
    private static string $username = 'mr-x@eteamid.com';
    private static string $password = '8gxe#71b`GIb';
    private static int $port = 465;
    private static string $secure = 'ssl'; // Use 'tls' if required
    private static int $priority = 3; // normal

    public static function sendEmail(string $toEmail, string $toName, string $subject, string $body, array $ccs = [], array $bccs = [])
    {
        // do not reply to self
        if ($toEmail === 'mr-x@eteamid.com') {
            return;
        }

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

            // priority
            $mail->Priority = static::$priority;

            // Recipients
            $mail->setFrom(static::$username, 'Mr X');
            $mail->addAddress($toEmail, $toName);

            foreach ($ccs as $cc) {
                $mail->addCC($cc);
            }

            foreach ($bccs as $bcc) {
                $mail->addBCC($bcc);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            try {
                $mail->send();
                return true;
            } catch (Exception $e) {
                logMessage('Failed to send email. Error: ' . $e->getMessage(), 'danger');
                return false;
            }

        } catch (Exception $e) {
            logMessage('Email could not be sent. Mailer Error: ' . $mail->ErrorInfo, 'danger');
            return false;
        }
    }

    public static function setHighPriority()
    {
        static::$priority = 1;
    }

    public static function resetHighPriority()
    {
        static::$priority = 3;
    }
}
