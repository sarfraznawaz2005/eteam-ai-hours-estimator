<?php

class RemindBaseCampCustomers extends Task
{
    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            exit(1);
        }

        $isAlreadyDone = static::isDoneForToday(__CLASS__, __CLASS__);

        if ($isAlreadyDone) {
            return;
        }

        if (DEMO_MODE) {
            logMessage('DEMO_MODE: ' . __CLASS__);
            return;
        }

        $unrepliedMessages = [];

        $projects = BasecampClassicAPI::getAllProjects();

        $userIds = array_flip(BasecampClassicAPI::getAllUsers());

        // check in comments
        foreach ($projects as $projectId => $projectName) {
            // returns 25 most recent messages by default
            $messages = BasecampClassicAPI::getAllMessages($projectId);

            if (is_array($messages) && $messages) {
                foreach ($messages as $messageId => $messageDetails) {
                    $comments = BasecampClassicAPI::getAllComments($messageId);

                    if (is_array($comments) && $comments) {
                        $lastestComment = array_slice($comments, 0, 1, true);
                        $lastestComment = current($lastestComment) + ['key' => key($lastestComment)];

                        // we will only check for messages that have been not replied in 2 days
                        $days = new DateTime('2 days ago');
                        $maxDays = new DateTime('15 days ago');

                        $postedOn = new DateTime($lastestComment['created-at']);

                        if ($postedOn < $days && $postedOn > $maxDays && !in_array($lastestComment['author-id'], $userIds)) {
                            $unrepliedMessages[$messageId] = 'https://eteamid.basecamphq.com/projects/' . $projectId . '/posts/' . $messageId . '/comments#comment_' . $lastestComment['id'];
                        }
                    }
                }
            }

            //sleep(1);
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

                $dueReminders[] = $unrepliedMessages[$unrepliedMessageKey];

                static::markDone($unrepliedMessageKey, __CLASS__);
            }

            // send email
            if ($dueReminders) {
                $emailBody = "Dear All,<br><br>";
                $emailBody .= "Following customer messsages have not been replied since two days, please check if they need to be replied.<br><br>";

                $emailBody .= implode('<br>', array_map(function ($link) {
                    return '<a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a>';
                }, $dueReminders));

                $emailBody .= xSignature();
                
                EmailSender::sendEmail('sarfraz@eteamid.com', 'TEAM', 'Reminder - Un-Replied BaseCamp Customers', $emailBody);

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
