<?php

class ReplyToEmails extends Task
{
    const MRX_EMAIL_ADDRESS = 'mr-x@eteamid.com';

    // do not reply to these sender emails, must be lowercase
    private static array $excludedEmails = [
        'notifications@eteamid.basecamphq.com',
        'system@writeboard.com',
    ];

    // do not reply to emails with these subjects, must be lowercase
    private static array $ignoreSubjects = [
        'hours reminder'
    ];

    // for basecampe, if email body contains these words (value of array),
    // we can remind them by sending an email
    private static array $reminderWords = [
        'sarfraz@eteamid.com' => 'Sarfraz',
    ];

    public static function execute(): void
    {
        //logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            return;
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

                foreach ($emails as $emailNumber) {

                    usleep(500000); // 0.5 seconds

                    // Fetch full header information
                    $header = imap_headerinfo($inbox, $emailNumber);

                    $overview = imap_fetch_overview($inbox, $emailNumber);
                    $subject = $overview[0]->subject;
                    $emailBody = imap_fetchbody($inbox, $emailNumber, 2);

                    $toEmail = $header->to[0]->mailbox . "@" . $header->to[0]->host;
                    $fromEmail = $header->from[0]->mailbox . "@" . $header->from[0]->host;
                    $fromName = $header->from[0]->personal ?? $fromEmail;

                    // do not consider self
                    if ($fromEmail === self::MRX_EMAIL_ADDRESS) {
                        static::imapCleanup($inbox, $emailNumber);

                        continue;
                    }

                    ////////////////////////////////////////////////
                    // send basecamp mention reminders
                    ////////////////////////////////////////////////

                    // remove basecamp footer
                    $messageBody = preg_replace('/This (comment|message) was sent to.*/s', '', $emailBody);

                    foreach (static::$reminderWords as $email => $word) {

                        // we remind only for basecamp notifications
                        if (
                            str_contains(strtolower($fromEmail), 'basecamphq') &&
                            (str_contains(strtolower($messageBody), strtolower($word)) || str_contains(strtolower($subject), strtolower($word))
                            )) {

                            // do not consider if message has been sent by actual user himself.
                            $pattern = "/$word).*?posted a new message:/i";

                            if (preg_match_all($pattern, $messageBody)) {
                                static::imapCleanup($inbox, $emailNumber);

                                continue;
                            }

                            $body = "Dear $email,<br><br>";
                            $body .= "The '$word' has been mentioned in following message on basecamp.<br><br>";
                            $body .= "---<br><br><i>$messageBody</i><br><br>---";
                            $body .= xSignature();

                            EmailSender::setHighPriority();
                            $emailSent = EmailSender::sendEmail($email, $word, 'You have been mentioned!', $body);
                            // because it is static class, don't want to affect other places this class is used in.
                            EmailSender::resetHighPriority();

                            if ($emailSent) {
                                logMessage(__CLASS__ . " : Name Reminder Sent For $word", 'success');

                                static::imapCleanup($inbox, $emailNumber);

                                break;
                            }
                        }
                    }
                    ////////////////////////////////////////////////

                    // do not reply to excluded email subjects
                    foreach (static::$ignoreSubjects as $ignoreSubject) {
                        if (str_contains(strtolower($subject), strtolower($ignoreSubject))) {
                            static::imapCleanup($inbox, $emailNumber);

                            // break both inner and outer loop
                            break 2;
                        }
                    }

                    // do not reply to excluded sender emails
                    if (in_array(strtolower($fromEmail), static::$excludedEmails, true)) {
                        static::imapCleanup($inbox, $emailNumber);

                        continue;
                    }

                    logMessage(__CLASS__ . " : Going to send email to: $fromEmail");

                    // include CC recipients in the reply
                    $ccEmails = [];
                    if (isset($header->cc) && is_array($header->cc)) {
                        foreach ($header->cc as $cc) {
                            $ccEmails[] = $cc->mailbox . "@" . $cc->host;
                        }
                    }

                    // we want to reply when we are mentioned or email is sent to our email address
                    if (
                        str_contains(strtolower($emailBody), strtolower(MENTION_TEXT)) ||
                        str_contains(strtolower($subject), strtolower(MENTION_TEXT)) ||
                        $toEmail === self::MRX_EMAIL_ADDRESS
                    ) {

                        $prompt = <<<PROMPT
                            \n\n

                            You are helpful assistant tasked with replying emails in a polite and professional manner. When someone mentions you
                            by "@mrx", your job then is to see contents of email and reply in detail with clear and easy to understand manner.

                            Use following format for reply:

                            Dear $fromName,

                            [Your reply to $emailBody goes here]

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
                        if (strtolower(strip_tags($response)) === 'ok') {
                            static::imapCleanup($inbox, $emailNumber);

                            continue;
                        }

                        try {

                            $subject = 'Re: ' . $subject;

                            if (!str_contains(strtolower($response), 'no response')) {

                                $decodedEmailBody = quoted_printable_decode($emailBody);
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
                                    logMessage(__CLASS__ . " : Email has been sent: $subject", 'success');

                                    // Mark the message for deletion after successfully sending the reply
                                    imap_delete($inbox, $emailNumber);

                                } else {
                                    logMessage(__CLASS__ . " : Error or no response: $subject", 'danger');
                                }
                            } else {
                                logMessage(__CLASS__ . " : Error or no response: $subject", 'danger');
                            }

                        } catch (Exception $e) {
                            logMessage(__CLASS__ . ' : Email could not be sent. Mailer Error: ' . $e->getMessage(), 'danger');
                            imap_close($inbox);
                        }
                    }
                }
            }

            try {

                // Clean up and expunge messages marked for deletion
                if (is_object($inbox)) {
                    //@imap_ping($inbox); // Suppress errors to handle them manually

                    if (empty(imap_errors())) {
                        @imap_expunge($inbox);
                        @imap_close($inbox);
                    }
                }

            } catch (Exception) {
            }

        }, 2);

    }

    private static function imapCleanup($inbox, $emailNumber): void
    {
        try {

            if (is_object($inbox)) {
                //@imap_ping($inbox); // Suppress errors to handle them manually

                if (empty(imap_errors())) {
                    // Mark the message for deletion after successfully sending the reply
                    @imap_delete($inbox, $emailNumber);

                    // Clean up and expunge messages marked for deletion
                    @imap_expunge($inbox);

                    @imap_close($inbox);
                }
            }

        } catch (Exception) {
        }
    }
}
