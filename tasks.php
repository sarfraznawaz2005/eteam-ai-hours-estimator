<?php

function checkInboxForReplies()
{
    logMessage('Running: ' . __FUNCTION__);

    // IMAP connection details
    $hostname = '{imap.eteamid.com:993/imap/ssl}INBOX'; // Adjust this as per your IMAP server details
    $username = 'sarfraz@eteamid.com';
    $password = '@}24v94ztB2{';

    // Connect to the mailbox
    $inbox = imap_open($hostname, $username, $password) or die('Cannot connect to email: ' . imap_last_error());

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

            $toEmail = $header->from[0]->mailbox . "@" . $header->from[0]->host;
            $toName = isset($header->from[0]->personal) ? $header->from[0]->personal : $toEmail;

            // Include CC recipients in the reply
            $ccEmails = [];
            if (isset($header->cc) && is_array($header->cc)) {
                foreach ($header->cc as $cc) {
                    $ccEmails[] = $cc->mailbox . "@" . $cc->host;
                }
            }

            $mentionText = '@mrx';

            // we want to reply when we are mentioned or email is sent to our email address
            if (
                str_contains(strtolower($email_body), $mentionText) ||
                str_contains(strtolower($subject), $mentionText) ||
                $toEmail === 'mr-x@eteamid.com'
            ) {
                $prompt = <<<PROMPT
            \n\n

            Your name is "Mr-X", created by "Sarfraz Ahmed" at eTeam. You are helpful assistant tasked with replying emails in a
            polite and professional manner. Your job is to see contents of email and reply in detail with clear and easy to
            understand manner.

            Use following format for reply:

                Dear $toName,

                [Your reply to $email_body goes here]

                _Thanks_

            ---

            Mr X -
            eTeam Bot

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
                        $emailSent = EmailSender::sendEmail($toEmail, $toName, $subject, $response, $ccEmails);

                        if ($emailSent) {
                            logMessage("Inbox: Email has been sent: {$subject}");
                        } else {
                            logMessage("Inbox: Error or no response: {$subject}", 'error');
                        }
                    } else {
                        logMessage("Inbox: Error or no response: {$subject}", 'error');
                    }

                } catch (Exception $e) {
                    logMessage('Inbox: Email could not be sent. Mailer Error: ' . $e->getMessage(), 'error');
                }
            }

            sleep(3);
        }
    }

    imap_close($inbox);
}

function postWorkPlan()
{
    logMessage('Running: ' . __FUNCTION__);

    $isAlreadyDone = IniReader::get(__FUNCTION__);

    if (!$isAlreadyDone) {

        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();
        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);

        if (is_array($eteamMiscProjectMessages) && $eteamMiscProjectMessages) {
            $messsageId = key(array_slice($eteamMiscProjectMessages, 0, 1, true));
            $messageValue = reset($eteamMiscProjectMessages);

            if (
                str_contains(strtolower($messageValue), 'workplan') ||
                str_contains(strtolower($messageValue), 'work plan')
            ) {

                $message = <<<message
                AOA,<br><br>

                <b>Misc</b>:<br>
                    - Send Today's Project Idea<br>
                    - Code Review<br>
                    - Replying to Customer Emails<br>
                    - Replying to Basecamp Messages<br>
                    - Estimate Projects<br>
                    - Create System Plan For New Projects<br>
                    - Provide Database Support<br>
                    - SEO Optimizations<br>
                    - Coordinate with Team<br>
                    - Etc
                message;

                GoogleAI::setPrompt("Please provide a inspirational quote tailored to our software engineering company. This inspirational quote should boost the morale of our team.");

                $response = GoogleAI::GenerateContentWithRetry();

                if (!str_contains(strtolower($response), 'no response')) {
                    $message .= <<<message
                    <br><br><b>Inspirational Quote Of The Day:</b><br>

                    $response
                    message;
                }

                $action = "posts/$messsageId/comments.xml";

                $xmlData = <<<data
                    <comment>
                        <body><![CDATA[$message]]></body>
                    </comment>
                data;

                // send to basecamp
                $response = BasecampClassicAPI::postInfo($action, $xmlData);

                if ($response && $response['code'] === 201) {
                    logMessage("postWorkPlan: Success");

                    IniReader::set(__FUNCTION__, 'true');
                } else {
                    logMessage("postWorkPlan: Could not post workplan", 'error');
                }
            }
        }
    }
}

function postProjectIdea()
{
    logMessage('Running: ' . __FUNCTION__);

    $isWorkplanPosted = IniReader::get('postWorkPlan');

    // we only send project idea after we have posted workplan
    if (!$isWorkplanPosted) {
        return;
    }

    $isAlreadyDone = IniReader::get('__FUNCTION__');

    if (!$isAlreadyDone) {

        GoogleAI::setPrompt(file_get_contents('./tools/idea-generator/prompt.txt') . "\n\nPlease generate a random software product idea based on given instructions.");

        $response = GoogleAI::GenerateContentWithRetry();

        if (!str_contains(strtolower($response), 'no response')) {

            $userIds = BasecampClassicAPI::getAllUsers();

            $notifyPersonsXml = '';

            foreach (array_keys($userIds) as $key) {
                $notifyPersonsXml .= "<notify>$key</notify>\n";
            }

            $postTitle = 'Idea Of The Day - ' . date('d-m-Y');

            $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();

            $action = "projects/$eteamMiscTasksProjectId/posts.xml";

            $xmlData = <<<data
            <request>
                <post>
                    <title>$postTitle</title>
                    <body><![CDATA[$response]]></body>
                </post>
                $notifyPersonsXml
            </request>
            data;

            // send to basecamp
            $response = BasecampClassicAPI::postInfo($action, $xmlData);

            if ($response && $response['code'] === 201) {
                logMessage("getProjectIdea: Success");

                IniReader::set(__FUNCTION__, 'true');
            } else {
                logMessage("getProjectIdea: Could not post workplan", 'error');
            }

        } else {
            logMessage("getProjectIdea: Error or no response", 'error');
        }
    }

}

function replyToBaseCampMessages()
{
    // todo
}
