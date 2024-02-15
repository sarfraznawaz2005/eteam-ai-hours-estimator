<?php

class RemindMyNameBaseCamp extends Task
{
    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            exit(1);
        }

        if (DEMO_MODE) {
            logMessage('DEMO_MODE: ' . __CLASS__);
            return;
        }

        $unrepliedMessages = [];
        $projects = BasecampClassicAPI::getAllProjects();

        // check in messages
        foreach ($projects as $projectId => $projectName) {
            // returns 25 most recent messages by default
            $messages = BasecampClassicAPI::getAllMessages($projectId);

            if (is_array($messages) && $messages) {
                $lastestMessage = array_slice($messages, 0, 1, true);
                $lastestMessage = current($lastestMessage) + ['key' => key($lastestMessage)];

                $messageTitle = $lastestMessage['title'] ?? '';
                $messageBody = $lastestMessage['body'] ?? '';

                if (
                    str_contains(strtolower(trim(strip_tags($messageTitle))), 'sarfraz') ||
                    str_contains(strtolower(trim(strip_tags($messageBody))), 'sarfraz')
                ) {
                    $unrepliedMessages[$projectId] = 'https://eteamid.basecamphq.com/projects/' . $projectId . '/posts/' . $lastestMessage['id'];
                }
            }

            //sleep(1);
        }

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

                        $commentBody = $lastestComment['body'] ?? '';

                        if (str_contains(strtolower(trim(strip_tags($commentBody))), 'sarfraz')) {
                            $unrepliedMessages[$messageId] = 'https://eteamid.basecamphq.com/projects/' . $projectId . '/posts/' . $messageId . '/comments#comment_' . $lastestComment['id'];
                        }
                    }
                }
            }
        }

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
                $emailBody = "Dear Sarfraz,<br><br>";
                $emailBody .= "You have been mentioned in following messages on basecamp.<br><br>";

                $emailBody .= implode('<br>', array_map(function ($link) {
                    return '<a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a>';
                }, $dueReminders));

                $emailSent = EmailSender::sendEmail('sarfraz@eteamid.com', 'Sarfraz', 'You have been mentioned!', $emailBody);

                if ($emailSent) {
                    logMessage(__CLASS__ . ' : Name Reminder Sent', 'success');
                }
            }

        }

    }
}
