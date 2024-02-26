<?php

class RemindHours extends Task
{
    /**
     * @throws Exception
     */
    public static function execute(): void
    {
        logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            return;
        }

        // we do not run this after this time
        if (!isTimeInRange('2:00PM')) {
            return;
        }

        $id = date('Y-m-d');

        $isAlreadyDone = static::isDoneForToday($id, __CLASS__);

        if ($isAlreadyDone) {
            return;
        }

        if (self::shouldRemind()) {
            if (self::isLastDayOfMonth()) {
                $emailBody = "Dear Team,<br><br>Kindly upload all hours before day end today.";
                $emailBody .= xSignature();

                $emailSent = EmailSender::sendEmail('everyone@eteamid.com', 'Everyone', 'Hours Reminder', $emailBody);

                if ($emailSent) {
                    logMessage(__CLASS__ . ' : Hours Reminder', 'success');
                }
            }

            self::markItDone($id);
        }

    }

    private static function markItDone($id): void
    {
        $result = static::markDone($id, __CLASS__);

        if ($result) {
            logMessage(__CLASS__ . ' : Marked Done', 'success');
        } else {
            logMessage(__CLASS__ . ' : Unable to mark done', 'danger');
        }
    }

    private static function isLastDayOfMonth(): bool
    {
        $today = date('Y-m-d');

        $lastDayOfMonth = date('Y-m-t', strtotime($today));

        if ($today == $lastDayOfMonth) {
            return true;
        }

        return false;
    }

    // remind only if it's working day, check if users have posted workplan

    /**
     * @throws Exception
     */
    private static function shouldRemind(): bool
    {
        // don't do for weekends
        if (date('D') === 'Sun' || date('D') === 'Sat') {
            return false;
        }

        // also check for workplans making sure today is not public holiday
        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();

        if (!$eteamMiscTasksProjectId) {
            logMessage('Failed to get the eteam misc tasks project ID. Please verify that the project exists and is accessible.', 'danger');
            return false;
        }

        // returns 25 most recent messages by default
        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);

        if ($eteamMiscProjectMessages) {
            $messages = array_slice($eteamMiscProjectMessages, 0, 1, true);

            if ($messages) {
                $messageDate = $messages[key($messages)]['posted-on'];

                // we only process for today post
                if (isDateToday($messageDate)) {
                    foreach ($messages as $messageDetails) {
                        $messageTitle = $messageDetails['title'];

                        if (
                            str_starts_with(strtolower(trim($messageTitle)), 'workplan') ||
                            str_starts_with(strtolower(trim($messageTitle)), 'work plan')
                        ) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
