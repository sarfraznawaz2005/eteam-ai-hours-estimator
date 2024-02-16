<?php

class ReplyToEmails extends Task
{
    private static $excludedEmails = [
        'notifications@eteamid.basecamphq.com',
    ];

    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            exit(1);
        }

        // IMAP connection details
        //$hostname = '{imap.eteamid.com:993/imap/ssl}INBOX'; // this was giving certificate error online
        $hostname = '{imap.eteamid.com:993/imap/ssl/novalidate-cert}';
        $username = 'mr-x@eteamid.com';
        $password = '8gxe#71b`GIb';

        retry(function () use ($hostname, $username, $password) {

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

                    sleep(3);

                    // Fetch full header information
                    $header = imap_headerinfo($inbox, $email_number);

                    $overview = imap_fetch_overview($inbox, $email_number, 0);
                    $subject = $overview[0]->subject;
                    $email_body = imap_fetchbody($inbox, $email_number, 2);

                    $toEmail = $header->to[0]->mailbox . "@" . $header->to[0]->host;
                    $fromEmail = $header->from[0]->mailbox . "@" . $header->from[0]->host;
                    $fromName = isset($header->from[0]->personal) ? $header->from[0]->personal : $fromEmail;

                    // do not reply to excluded sender emails
                    if (in_array($fromEmail, static::$excludedEmails, true)) {
                        continue;
                    }

                    // Include CC recipients in the reply
                    $ccEmails = [];
                    if (isset($header->cc) && is_array($header->cc)) {
                        foreach ($header->cc as $cc) {
                            $ccEmails[] = $cc->mailbox . "@" . $cc->host;
                        }
                    }

                    $mentionText = MENTION_TEXT;

                    // we want to reply when we are mentioned or email is sent to our email address
                    if (
                        str_contains(strtolower($email_body), $mentionText) ||
                        str_contains(strtolower($subject), $mentionText) ||
                        $toEmail === 'mr-x@eteamid.com'
                    ) {

                        $prompt = <<<PROMPT
                            \n\n

                            You are helpful assistant tasked with replying emails in a polite and professional manner. When someone mentions you
                            by "@mrx", your job then is to see contents of email and reply in detail with clear and easy to understand manner.
                            You must only reply if there is some sort of question or query, if you think there is nothing to reply then ignore
                            further instructions and just reply with "OK".

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
                            $subject = 'Re: ' . imap_headerinfo($inbox, $email_number)->subject;

                            if (!str_contains(strtolower($response), 'no response')) {
                                $emailSent = EmailSender::sendEmail($fromEmail, $fromName, $subject, $response, $ccEmails);

                                if ($emailSent) {
                                    logMessage(__CLASS__ . " : Email has been sent: {$subject}", 'success');
                                } else {
                                    logMessage(__CLASS__ . " : Error or no response: {$subject}", 'danger');
                                }
                            } else {
                                logMessage(__CLASS__ . " : Error or no response: {$subject}", 'danger');
                            }

                        } catch (Exception $e) {
                            logMessage(__CLASS__ . ' : Email could not be sent. Mailer Error: ' . $e->getMessage(), 'danger');
                        }
                    }
                }
            }

            imap_close($inbox);

        }, 2);

    }
}
