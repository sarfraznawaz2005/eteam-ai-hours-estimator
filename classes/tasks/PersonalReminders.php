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
        if (!isTimeInRange('5:00PM')) {
            return;
        }

        self::checkReminder('Hours Upload Reminder', date('t'), 'everyone@eteamid.com', 'Dear Team,<br><br>Kindly upload all hours for this month.');
        self::checkReminder('Net Bill Reminder', date('t'), 'sarfraz@eteamid.com', 'Dear Sarfraz,<br><br>Kindly pay internet bill today.');
        self::checkReminder('K-Electric Bill Reminder', '17', 'sarfraz@eteamid.com', 'Dear Sarfraz,<br><br>Kindly pay K-Electric bills today.');
        self::checkReminder('Team Salaries Reminder', '04', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for Team Salaries.');
        self::checkReminder('FBL Hoe Loan Instalment Reminder', '06', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for FBL Hoe Loan Instalment.');
        self::checkReminder('Complete Invoicing Reminder', '07', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for Complete Invoicing.');
        self::checkReminder('Office and Home Utilities Reminder', '08', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for Office and Home Utilities.');
        self::checkReminder('Team Allowances Reminder', '12', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for Team Allowances.');
        self::checkReminder('FBR & SRB Tax Challans Reminder', '13', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for FBR & SRB Tax Challans.');
        self::checkReminder('CC Payments Reminder', '20', 'riaz@eteamid.com', 'Dear Riaz,<br><br>This is your reminder for CC Payments.');
        self::checkReminder('AC & Solar Plate Cleaning Reminder', '01', 'sarfraz@eteamid.com', 'Dear Sarfraz,<br><br>Please clean AC & solar plates today');
        self::checkReminder('AC & Solar Plate Cleaning Reminder', '15', 'sarfraz@eteamid.com', 'Dear Sarfraz,<br><br>Please clean AC & solar plates today');

    }

    private static function checkReminder($prefix, $day, $to, $body): void
    {
        $todayDate = date('Y-m-d');

        if (date('d') == $day) {
            $id = "{$prefix}_{$todayDate}_$to";

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

        if (!$result) {
            logMessage(__CLASS__ . ' : Unable to mark done', 'danger');
        }
    }
}
