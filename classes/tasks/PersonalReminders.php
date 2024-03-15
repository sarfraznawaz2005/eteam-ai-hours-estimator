<?php

class PersonalReminders extends Task
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

        self::checkReminder('Sarfraz Net Bill Reminder', '15', 'sarfraz@eteamid.com', 'Dear Sarfraz,<br><br>Kindly pay internet bill today.');
        self::checkReminder('Sarfraz Net Bill Reminder', '14', 'sarfraz@eteamid.com', 'Dear Sarfraz,<br><br>Kindly pay internet bill today.');
        self::checkReminder('Sarfraz Net Bill Reminder', '16', 'sarfraz@eteamid.com', 'Dear Sarfraz,<br><br>Kindly pay internet bill today.');

        self::checkReminder('Sarfraz Net Bill Reminder', date('Y-m-t', strtotime(date('Y-m-d'))), 'sarfraz@eteamid.com', 'Dear Sarfraz,<br><br>Kindly pay internet bill today.');
        self::checkReminder('K-Electric Bill Reminder', '15', 'sarfraz@eteamid.com', 'Dear Sarfraz,<br><br>Kindly pay K-Electric bills today.');
        self::checkReminder('Team Salaries Reminder', '4', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for Team Salaries.');
        self::checkReminder('FBL Hoe Loan Instalment Reminder', '6', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for FBL Hoe Loan Instalment.');
        self::checkReminder('Complete Invoicing Reminder', '7', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for Complete Invoicing.');
        self::checkReminder('Office and Home Utilities Reminder', '8', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for Office and Home Utilities.');
        self::checkReminder('Team Allowances Reminder', '12', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for Team Allowances.');
        self::checkReminder('FBR & SRB Tax Challans Reminder', '13', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for FBR & SRB Tax Challans.');
        self::checkReminder('CC Payments Reminder', '20', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for CC Payments.');

    }

    private static function checkReminder($prefix, $day, $to, $body): void
    {
        $todayDate = date('Y-m-d');

        if (date('d') === $day) {
            $id = "${$prefix}_$todayDate";

            $isAlreadyDone = static::isDoneForToday($id, __CLASS__);

            if (!$isAlreadyDone) {
                $emailSent = self::sendEmail($to, $prefix, $body);

                if ($emailSent) {
                    logMessage(__CLASS__ . " : $prefix", 'success');
                }

                self::markItDone($id);
            }
        }
    }

    private static function sendEmail($to, $subject, $body): bool
    {
        return EmailSender::sendEmail($to, $to, $subject, $body . xSignature());
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
