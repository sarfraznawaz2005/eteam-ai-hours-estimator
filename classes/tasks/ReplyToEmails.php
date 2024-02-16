<?php

class ReplyToEmails extends Task
{
    const MRX_EMAIL_ADDRESS = 'mr-x@eteamid.com';

    private static array $excludedEmails = [
        'notifications@eteamid.basecamphq.com',
    ];

    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            exit(1);
        }

        retry(function () {
            $password = '8gxe#71b`GIb';
            $username = self::MRX_EMAIL_ADDRESS;

            //$hostname = '{imap.eteamid.com:993/imap/ssl}INBOX'; // this was giving certificate error online
            $hostname = '{imap.eteamid.com:993/imap/ssl/novalidate-cert}';

            // Connect to the mailbox
            $inbox = imap_open($hostname, $username, $password);

            if (!$inbox) {
                logMessage('Error: ' . imap_last_error(), 'danger');
                return;
            }

            $emails = [];
            $allEmails = imap_search($inbox, 'UNSEEN'); // SEEN OR UNSEEN

            if ($allEmails) {
                $emails = array_slice($allEmails, -10);
            }

            //print_r($emails);

            if ($emails) {

                foreach ($emails as $email_number) {

                    usleep(500000);

                    // Fetch full header information
                    $header = imap_headerinfo($inbox, $email_number);

                    $overview = imap_fetch_overview($inbox, $email_number, 0);
                    $subject = $overview[0]->subject;
                    $email_body = imap_fetchbody($inbox, $email_number, 2);

                    $toEmail = $header->to[0]->mailbox . "@" . $header->to[0]->host;
                    $fromEmail = $header->from[0]->mailbox . "@" . $header->from[0]->host;
                    $fromName = $header->from[0]->personal ?? $fromEmail;

                    // do not reply to excluded sender emails
                    if (in_array($fromEmail, static::$excludedEmails, true)) {
                        continue;
                    }

                    // do not reply to self
                    if ($fromEmail === self::MRX_EMAIL_ADDRESS) {
                        continue;
                    }

                    logMessage(__CLASS__ . " : Going to send email to: $toEmail");

                    // include CC recipients in the reply
                    $ccEmails = [];
                    if (isset($header->cc) && is_array($header->cc)) {
                        foreach ($header->cc as $cc) {
                            $ccEmails[] = $cc->mailbox . "@" . $cc->host;
                        }
                    }

                    $mentionText = strtolower(MENTION_TEXT);

                    // we want to reply when we are mentioned or email is sent to our email address
                    if (
                        str_contains(strtolower($email_body), $mentionText) ||
                        str_contains(strtolower($subject), $mentionText) ||
                        $toEmail === self::MRX_EMAIL_ADDRESS
                    ) {

                        $prompt = <<<PROMPT
                            \n\n

                            You are helpful assistant tasked with replying emails in a polite and professional manner. When someone mentions you
                            by "@mrx", your job then is to see contents of email and reply in detail with clear and easy to understand manner.

                            Use following format for reply:

                            Dear $fromName,

                            [Your reply to $email_body goes here]

                            _Thanks_

                            ---

                            Mr-X (eTeam AI Bot)
                            Technical Assistant

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

                        // if there is nothing to reply, don't do anything
                        if (strtolower($response) === 'ok') {
                            continue;
                        }

                        try {

                            $subject = 'Re: ' . $subject;

                            if (!str_contains(strtolower($response), 'no response')) {

                                $decodedEmailBody = quoted_printable_decode($email_body);
                                $decodedEmailBody = '<blockquote>' . $decodedEmailBody . '</blockquote>';

                                // Prepare the email content with the response and the original message
                                $response .= <<<original
                                <br>
                                ---
                                <br>
                                <i>
                                Original Message:
                                <br>
                                $decodedEmailBody
                                </i>
                                original;

                                $emailSent = EmailSender::sendEmail($fromEmail, $fromName, $subject, $response, $ccEmails, ['sarfraz@eteamid.com']);

                                if ($emailSent) {
                                    logMessage(__CLASS__ . " : Email has been sent: {$subject}", 'success');

                                    // Mark the message for deletion after successfully sending the reply
                                    imap_delete($inbox, $email_number);

                                } else {
                                    logMessage(__CLASS__ . " : Error or no response: {$subject}", 'danger');
                                }
                            } else {
                                logMessage(__CLASS__ . " : Error or no response: {$subject}", 'danger');
                            }

                        } catch (Exception $e) {
                            logMessage(__CLASS__ . ' : Email could not be sent. Mailer Error: ' . $e->getMessage(), 'danger');
                            imap_close($inbox);
                        }
                    }
                }
            }

            // Clean up and expunge messages marked for deletion
            imap_expunge($inbox);

            imap_close($inbox);

        }, 2);

    }
}
