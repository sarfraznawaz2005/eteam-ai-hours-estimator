<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once './vendor/autoload.php';
require_once './setup.php';

// IMAP connection details
$hostname = '{imap.eteamid.com:993/imap/ssl}INBOX'; // Adjust this as per your IMAP server details
$username = 'sarfraz@eteamid.com';
$password = '@}24v94ztB2{';

$smtp_host = 'mail.eteamid.com';
$smtp_username = 'sarfraz@eteamid.com';
$smtp_password = '@}24v94ztB2{';
$smtp_port = 465;
$smtp_secure = 'ssl'; // Use 'tls' if required

// Connect to the mailbox
$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to email: ' . imap_last_error());

$emails = [];
$allEmails = imap_search($inbox, 'SEEN');

if ($allEmails) {
    $emails = array_slice($allEmails, -25);
}

//print_r($emails);

if ($emails) {

    GoogleAI::SetConfig(getConfig());

    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        $subject = $overview[0]->subject;
        $email_body = imap_fetchbody($inbox, $email_number, 2);

        $specificText = '@mrx';

        if (strpos($email_body, $specificText) !== false || strpos($subject, $specificText) !== false) {
            $mail = new PHPMailer(true);

            // Fetch full header information
            $header = imap_headerinfo($inbox, $email_number);

            $toEmail = $header->from[0]->mailbox . "@" . $header->from[0]->host;
            $toName = isset($header->from[0]->personal) ? $header->from[0]->personal : $toEmail;

            // Include CC recipients in the reply
            if (isset($header->cc) && is_array($header->cc)) {
                foreach ($header->cc as $cc) {
                    $mail->addCC($cc->mailbox . "@" . $cc->host);
                }
            }

            $prompt = <<<PROMPT
            \n\n

            You are helpful assistant tasked with replying emails in a polite and professional manner.
            Your job is to see contents of email and reply accordingly.

            Use following format for reply:

                Dear $toName,

                Please see my reply to your email below:

                [Your reply to $email_body goes here]

                _Thanks_

            ---

            Mr X-Bot by eTeam
            Application Architect

            Enterprise Team (eTeam)
            607, Level 6,
            Ibrahim Trade Towers,
            Plot No.1 Block 7 & 8,
            MCHS, Main Shahrah-e-Faisal,
            Karachi-75400,
            Pakistan.
            Phone: +(9221) 37120414

PROMPT;

            GoogleAI::setPrompt($prompt);

            $response = GoogleAI::GenerateContentWithRetry();

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = $smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_username;
                $mail->Password = $smtp_password;
                $mail->SMTPSecure = $smtp_secure;
                $mail->Port = $smtp_port;

                // Recipients
                $mail->setFrom('mrx@eteamid.com', 'Mr X');
                $mail->addAddress($toEmail, $toName);
                //$mail->addBCC($email_bcc);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Re: ' . imap_headerinfo($inbox, $email_number)->subject;
                $mail->Body = $response;

                if (!str_contains($response, 'no response')) {
                    $mail->send();

                    echo "Message has been sent: {$mail->Subject}\n";
                } else {
                    echo "Error or no response: {$mail->Subject}\n";
                }

            } catch (Exception $e) {
                echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
            }
        }

        sleep(3);
    }
}

// Close the mailbox connection
imap_close($inbox);
