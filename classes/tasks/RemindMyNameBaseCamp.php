<?php

class RemindMyNameBaseCamp extends Task
{
    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            exit(1);
        }

        $unrepliedMessages = [];

        $projects = BasecampClassicAPI::getAllProjects();

        $allMessages = BasecampClassicAPI::getAllMessagesForAllProjectsParallel();

        // check in messages
        if ($allMessages) {
            foreach ($allMessages as $projectId => $messages) {
                // we get messages sorted by latest, so we only check latest message
                if (key($messages)) {
                    $message = $messages[key($messages)];

                    if (str_contains(strtolower($message['body']), 'sarfraz')) {
                        $unrepliedMessages[$message['id']] = 'https://eteamid.basecamphq.com/projects/' . $projectId . '/posts/' . $message['id'];
                    }
                }
            }
        }

        // check in comments
        foreach ($projects as $projectId => $projectName) {
            // returns 25 most recent messages by default
            $messages = BasecampClassicAPI::getAllMessages($projectId);

            if ($messages) {
                foreach ($messages as $messageId => $messageDetails) {
                    $comments = BasecampClassicAPI::getAllComments($messageId);

                    if ($comments) {
                        $lastestComment = array_slice($comments, 0, 1, true);
                        $lastestComment = current($lastestComment) + ['key' => key($lastestComment)];

                        $commentBody = $lastestComment['body'] ?? '';

                        if (str_contains(strtolower($commentBody), 'sarfraz')) {
                            $unrepliedMessages[$lastestComment['id']] = 'https://eteamid.basecamphq.com/projects/' . $projectId . '/posts/' . $messageId . '/comments#comment_' . $lastestComment['id'];
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

            if (DEMO_MODE) {
                logMessage('DEMO_MODE: ' . __CLASS__ . ' - Going to send email...');
                return;
            }

            // send email
            if ($dueReminders) {
                $emailBody = "Dear Sarfraz,<br><br>";
                $emailBody .= "You have been mentioned in following messages on basecamp.<br><br>";

                $emailBody .= implode('<br>', array_map(function ($link) {
                    return '<a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a>';
                }, $dueReminders));

                $emailBody .= xSignature();

                $emailSent = EmailSender::sendEmail('sarfraz@eteamid.com', 'Sarfraz', 'You have been mentioned!', $emailBody);

                if ($emailSent) {
                    logMessage(__CLASS__ . ' : Name Reminder Sent', 'success');
                }
            }

        }

    }
}
