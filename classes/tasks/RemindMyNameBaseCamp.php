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

        $allMessages = BasecampClassicAPI::getAllMessagesForAllProjectsParallel();
        $allComments = BasecampClassicAPI::getAllCommentsForAllPostsForAllProjectsParallel();

        // check in messages
        if (is_array($allMessages) && $allMessages) {
            foreach ($allMessages as $projectId => $messages) {
                // we get messages sorted by latest, so we only check latest message
                $message = array_slice($messages, 0, 1, true);

                if (isset($message['body']) && str_contains(strtolower($message['body']), 'sarfraz')) {
                    $unrepliedMessages[$message['id']] = 'https://eteamid.basecamphq.com/projects/' . $projectId . '/posts/' . $message['id'];
                }
            }
        }

        // check in comments
        if (is_array($allComments) && $allComments) {
            foreach ($allComments as $projectId => $messages) {
                foreach ($messages as $messageId => $comments) {
                    // we get comments sorted by latest, so we only check latest comment
                    $comment = array_slice($comments, 0, 1, true);

                    if (isset($comment['body']) && str_contains(strtolower($comment['body']), 'sarfraz')) {
                        $unrepliedMessages[$comment['id']] = 'https://eteamid.basecamphq.com/projects/' . $projectId . '/posts/' . $messageId . '/comments#comment_' . $comment['id'];
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

                $emailBody .= xSignature();

                $emailSent = EmailSender::sendEmail('sarfraz@eteamid.com', 'Sarfraz', 'You have been mentioned!', $emailBody);

                if ($emailSent) {
                    logMessage(__CLASS__ . ' : Name Reminder Sent', 'success');
                }
            }

        }

    }
}
