<?php

class CheckInboxForReplies extends Task
{
    public static function execute()
    {
        logMessage('Running: ' . __CLASS__);

        try {

            // IMAP connection details
            $hostname = '{imap.eteamid.com:993/imap/ssl}INBOX'; // Adjust this as per your IMAP server details
            $username = 'mr-x@eteamid.com';
            $password = '8gxe#71b`GIb';

            // Connect to the mailbox
            $inbox = imap_open($hostname, $username, $password);

            if (!$inbox) {
                logMessage('Error: ' . imap_last_error(), 'error');
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
                    // Fetch full header information
                    $header = imap_headerinfo($inbox, $email_number);

                    $overview = imap_fetch_overview($inbox, $email_number, 0);
                    $subject = $overview[0]->subject;
                    $email_body = imap_fetchbody($inbox, $email_number, 2);

                    
                    $toEmail = $header->to[0]->mailbox . "@" . $header->to[0]->host;
                    $fromEmail = $header->from[0]->mailbox . "@" . $header->from[0]->host;
                    $fromName = isset($header->from[0]->personal) ? $header->from[0]->personal : $fromEmail;

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

                            You are helpful assistant tasked with replying emails in a polite and professional manner. Your job is to
                            see contents of email and reply in detail with clear and easy to understand manner.

                            Use following format for reply:

                                Dear $fromName,

                                [Your reply to $email_body goes here]

                                _Thanks_

                            ---

                            Mr-X (eTeam Bot)
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

                        try {
                            $subject = 'Re: ' . imap_headerinfo($inbox, $email_number)->subject;
                            
                            if (!str_contains(strtolower($response), 'no response')) {
                                $emailSent = EmailSender::sendEmail($fromEmail, $fromName, $subject, $response, $ccEmails);

                                if ($emailSent) {
                                    logMessage(__CLASS__ . " : Email has been sent: {$subject}");
                                    //echo __CLASS__ . " : Email has been sent: {$subject}\n";
                                } else {
                                    logMessage(__CLASS__ . " : Error or no response: {$subject}", 'error');
                                }
                            } else {
                                logMessage(__CLASS__ . " : Error or no response: {$subject}", 'error');
                            }

                        } catch (Exception $e) {
                            logMessage(__CLASS__ . ' : Email could not be sent. Mailer Error: ' . $e->getMessage(), 'error');
                        }
                    }

                    sleep(3);
                }
            }

            imap_close($inbox);

        } catch (\Exception $e) {
            logMessage(__CLASS__ . " : Error : " . $e->getMessage() . "", 'error');
        }

    }
}
