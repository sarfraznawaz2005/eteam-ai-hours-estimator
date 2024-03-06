<?php

class MyPersonalReminder extends Task
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

        $todayDate = date('Y-m-d');

        // if today is last date of month
        if ($todayDate === date('Y-m-t', strtotime($todayDate))) {
            $id = "bill_reminder_$todayDate";

            $isAlreadyDone = static::isDoneForToday($id, __CLASS__);

            if (!$isAlreadyDone) {
                $emailSent = self::sendEmail('Net Bill Reminder', "Dear Sarfraz,<br><br>Kindly pay internet bill today.");

                if ($emailSent) {
                    logMessage(__CLASS__ . ' : Net Bill Reminder', 'success');
                }

                self::markItDone($id);
            }
        }

        // if today is 15th date of month
        if (date('d') === "15") {
            $id = "bills_reminder_$todayDate";

            $isAlreadyDone = static::isDoneForToday($id, __CLASS__);

            if (!$isAlreadyDone) {
                $emailSent = self::sendEmail('K-Electric Bill Reminder', "Dear Sarfraz,<br><br>Kindly pay K-Electric bills today.");

                if ($emailSent) {
                    logMessage(__CLASS__ . ' : K-Electric Bill Reminder', 'success');
                }

                self::markItDone($id);
            }
        }

    }

    private static function sendEmail($subject, $body): bool
    {
        return EmailSender::sendEmail('sarfraz@eteamid.com', 'Sarfraz', $subject, $body . xSignature());
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
}
