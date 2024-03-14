<?php

class RemindBaseCampCustomers extends Task
{
    /**
     * @throws Exception
     */
    public static function execute(): void
    {
        logMessage('Running: ' . __CLASS__);

        if (!isLuckyEnough(1)) {
            return;
        }

        if (static::isAlreadyRunning()) {
            return;
        }

        // we do not run this after this time
        if (!isTimeInRange('11:00PM')) {
            return;
        }

        $isAlreadyDone = static::isDoneForToday(__CLASS__, __CLASS__);

        if ($isAlreadyDone) {
            //logMessage('already done: ' . __CLASS__);
            return;
        }

        //logMessage('proceeding : ' . __CLASS__);

        $unrepliedMessages = [];

        $projects = BasecampClassicAPI::getAllProjects();

        $userIds = array_keys(BasecampClassicAPI::getAllUsers());

        /*
        // getAllMessagesForAllProjectsParallel doesn't seem to work fine on hosting
        // maybe due to some restrictions - didn't check further to fix it.
        $allMessages = BasecampClassicAPI::getAllMessagesForAllProjectsParallel();

        // check in messages
        if ($allMessages) {
        foreach ($allMessages as $projectId => $messages) {
        // we get messages sorted by latest, so we only check latest message
        if (key($messages)) {
        $message = $messages[key($messages)];

        // only if this doesn't have comments
        $comments = BasecampClassicAPI::getAllComments($message['id']);

        if ($comments) {
        continue;
        }

        // we will only check for messages that have been not replied in 2 days
        $days = new DateTime('2 days ago');
        $maxDays = new DateTime('15 days ago');

        $postedOn = new DateTime($message['posted-on']);

        if ($postedOn < $days && $postedOn > $maxDays && !in_array($message['author-id'], $userIds)) {
        $unrepliedMessages[$message['id']] = 'https://eteamid.basecamphq.com/projects/' . $projectId . '/posts/' . $message['id'];
        }
        }
        }
        }
         */

        // check in comments
        foreach ($projects as $projectId => $projectName) {
            // returns 25 most recent messages by default
            $messages = BasecampClassicAPI::getAllMessages($projectId);

            if ($messages) {
                foreach ($messages as $messageId => $messageDetails) {
                    usleep(500000); // 0.5 seconds

                    $comments = BasecampClassicAPI::getAllComments($messageId);

                    if ($comments) {
                        $lastestComment = array_slice($comments, 0, 1, true);
                        $lastestComment = current($lastestComment) + ['key' => key($lastestComment)];

                        // we will only check for messages that have been not replied in 2 days
                        $days = new DateTime('2 days ago');
                        $maxDays = new DateTime('15 days ago');

                        $postedOn = new DateTime($lastestComment['created-at']);

                        if ($postedOn < $days && $postedOn > $maxDays && !in_array($lastestComment['author-id'], $userIds)) {
                            $unrepliedMessages[$lastestComment['id']] = 'https://eteamid.basecamphq.com/projects/' . $projectId . '/posts/' . $messageId . '/comments#comment_' . $lastestComment['id'];
                        }
                    }
                }
            }
        }

        //dd($unrepliedMessages);

        if ($unrepliedMessages) {

            $dueReminders = [];
            $DB = new DB();

            // make sure we have not notified these before
            $lastAddedIdsDB = $DB->get(
                "select activity_id from activities where LOWER(description) = :description ORDER BY id DESC LIMIT 100",
                [':description' => strtolower(__CLASS__)]
            );

            $lastAddedIdsDB = $lastAddedIdsDB ?: [];

            $lastAddedIdsDB = array_map(function ($item) {
                return intval($item['activity_id'] ?? '0');
            }, $lastAddedIdsDB);

            foreach (array_keys($unrepliedMessages) as $unrepliedMessageKey) {
                if (in_array($unrepliedMessageKey, $lastAddedIdsDB)) {
                    continue;
                }

                $prompt = <<<EOD
                See given customer message and figure out if we should reply to it in case it is a question/query or 
                even a general comment that you think is worth replying. If it's worth replying, reply with only and 
                exactly "Worth Replying" and nothing else. If it's not worth replying, reply with "All Good" instead.
                
                Customer Message: "$unrepliedMessages[$unrepliedMessageKey]"
                EOD;

                GoogleAI::setPrompt($prompt);

                $response = strtolower(GoogleAI::GenerateContentWithRetry());

                if (str_contains($response, 'worth replying')) {
                    $dueReminders[] = $unrepliedMessages[$unrepliedMessageKey];
                    static::markDone($unrepliedMessageKey, __CLASS__);
                }
            }

            if (DEMO_MODE) {
                logMessage('DEMO_MODE: ' . __CLASS__ . ' - Going to send email...');
                return;
            }

            // send email
            if ($dueReminders) {
                $emailBody = "Dear All,<br><br>";
                $emailBody .= "Following customer messsages have not been replied since two days, please check if they need to be replied.<br><br>";

                $emailBody .= implode('<br>', array_map(function ($link) {
                    return '<a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a>';
                }, $dueReminders));

                $emailBody .= xSignature();

                EmailSender::sendEmail('everyone@eteamid.com', 'TEAM', 'Reminder - Un-Replied BaseCamp Customers', $emailBody);

                logMessage(__CLASS__ . ' : Reminder Email Sent', 'success');
            }

        } else {
            logMessage(__CLASS__ . ' : No Messages To Remind.');
        }

        if (!DEMO_MODE) {
            // so we don't run this job again today
            static::markDone(__CLASS__, __CLASS__);
        }

    }
}
