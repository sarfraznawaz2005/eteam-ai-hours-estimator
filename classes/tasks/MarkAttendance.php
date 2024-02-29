<?php

class MarkAttendance extends Task
{
    const SPREAD_SHEET_ID = '1A6VP8uTogoO1xMXvfpkFidPl1ER791OYOhoYnLMbRsQ';

    protected static int $totalNewPostsToFetch = 1; // since we check only latest single post

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
        if (!isTimeInRange('3:00PM')) {
            return;
        }

        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();
        //dd($eteamMiscTasksProjectId);

        if (!$eteamMiscTasksProjectId) {
            logMessage('Failed to get the eteam misc tasks project ID. Please verify that the project exists and is accessible.', 'danger');
            return;
        }

        // returns 25 most recent messages by default
        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);

        if ($eteamMiscProjectMessages) {

            $DB = new DB();

            //////////////////////////////////
            // delete older records
            $description = __CLASS__;
            $sql = "DELETE FROM activities WHERE description = '$description' AND DATE(created_at) < DATE(NOW() - INTERVAL 10 DAY)";
            $DB->executeQuery($sql);
            //////////////////////////////////

            $messages = array_slice($eteamMiscProjectMessages, 0, static::$totalNewPostsToFetch, true);

            if ($messages) {
                $messageDate = $messages[key($messages)]['posted-on'];

                // we only process for today post
                if (!isDateToday($messageDate)) {
                    return;
                }
            }

            $lastAddedIdsDB = $DB->get(
                "select activity_id from activities where LOWER(description) = :description ORDER BY id DESC LIMIT 50",
                [':description' => strtolower(__CLASS__)]
            );

            $lastAddedIdsDB = $lastAddedIdsDB ?: [];

            $lastAddedIdsDB = array_map(function ($item) {
                return intval($item['activity_id'] ?? '0');
            }, $lastAddedIdsDB);
            //dd($lastAddedIdsDB);

            foreach ($messages as $messageId => $messageDetails) {
                $messageTitle = $messageDetails['title'];

                if (
                    str_starts_with(strtolower(trim($messageTitle)), 'workplan') ||
                    str_starts_with(strtolower(trim($messageTitle)), 'work plan')
                ) {

                    // mark for message poster
                    if (!in_array($messageId, $lastAddedIdsDB)) {
                        //echo "\nfor message poster";
                        static::checkAndMarkAttendance($messageId, $messageDetails, $messageId);
                    }

                    // mark for message commenters
                    $messageComments = BasecampClassicAPI::getAllComments($messageId);

                    if ($messageComments) {
                        foreach ($messageComments as $commentId => $commentDetails) {
                            usleep(500000); // 0.5 seconds

                            if (!in_array($commentId, $lastAddedIdsDB)) {
                                static::checkAndMarkAttendance($messageId, $commentDetails, $commentId);
                            }
                        }
                    }
                }
            }
        }
    }

    private static function checkAndMarkAttendance($messageId, $details, $activityId): void
    {
        if (DEMO_MODE) {
            logMessage('DEMO_MODE: ' . __CLASS__ . " : Going to mark attendance for " . $details['author-name']);
            return;
        }

        $userIds = array_keys(BasecampClassicAPI::getAllUsers());

        // we do this only for company employees
        if (!in_array($details['author-id'], $userIds)) {
            return;
        }

        // do not count mr-x
        if (BasecampClassicAPI::$userId == $details['author-id']) {
            return;
        }

        $messageAuthorName = $details['author-name'];

        $result = self::getAttendance($messageAuthorName);

        if ($result !== false && (trim($result) === '' || trim(strtolower($result)) === 'o')) {
            $attendanceValue = 'P';
            $body = $details['body'];

            if (
                str_contains(strtolower($body), 'home') ||
                strtolower($messageAuthorName) === 'sarfraz ahmed' ||
                strtolower($messageAuthorName) === 'usama kafeel'
            ) {
                $attendanceValue = 'W';
            }

            if (date('l') === "Saturday" || date('l') === "Sunday") {
                $attendanceValue = 'C';
            }

            $result = self::markAttendance($messageAuthorName, $attendanceValue);

            if ($result) {

                static::markDone($activityId, __CLASS__);
                logMessage(__CLASS__ . " :  $messageAuthorName", 'success');

                $comment = "Dear $messageAuthorName, I have marked your attendance for today, Thanks!";

                $action = "posts/$messageId/comments.xml";

                $xmlData = <<<data
                <comment>
                <body><![CDATA[$comment]]></body>
                </comment>
                data;

                BasecampClassicAPI::postInfo($action, $xmlData);

            } else {
                logMessage(__CLASS__ . " :  Unable to mark attendance for $messageAuthorName", 'error');
            }

        } else {
            logMessage(__CLASS__ . " :  Attendance already marked by $messageAuthorName");
        }
    }

    private static function sendRequest($employeeName, $isPost = false, $attendanceValue = 'P')
    {
        $url = "https://script.google.com/macros/s/AKfycbyiUDs2pZRhwg0OK1WBZVB08skhpxsvf5i424kGBbJ9QwXlemqJOW_ddYbJLq76HQcg/exec";

        $payloadArray = [
            "spreadsheetId" => self::SPREAD_SHEET_ID,
            "employeeName" => $employeeName,
            "attendanceValue" => $attendanceValue,
        ];

        if ($isPost) {
            $payloadArray['requestType'] = 'post';
        }

        $payload = json_encode($payloadArray);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        //var_dump($response);exit;

        $jsonResponse = json_decode($response, true);

        if ($jsonResponse !== null) {
            return $jsonResponse;
        }

        return false;
    }

    private static function getAttendance($employeeName)
    {
        $result = self::sendRequest($employeeName);

        if ($result) {
            if ($result['status'] ?? '' === "success") {
                return $result['message'];
            }
        }

        return false;
    }

    private static function markAttendance($employeeName, $attendanceValue = 'P')
    {
        $result = self::sendRequest($employeeName, true, $attendanceValue);

        if ($result) {
            if ($result['status'] ?? '' === "success") {
                return true;
            }
        }

        return false;
    }
}
