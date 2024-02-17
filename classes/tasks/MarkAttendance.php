<?php

class MarkAttendance extends Task
{
    const SPREAD_SHEET_ID = '10Za5gH9C5QkxxuAKTRhSItwXaC7L1Qblb564_HumNNk';

    protected static int $totalNewPostsToFetch = 25;

    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            exit(1);
        }

        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();
        //dd($eteamMiscTasksProjectId);

        if (!$eteamMiscTasksProjectId) {
            logMessage('Failed to get the eteam misc tasks project ID. Please verify that the project exists and is accessible.', 'danger');
            return;
        }

        // returns 25 most recent messages by default
        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);
        //dd($eteamMiscProjectMessages);

        if ($eteamMiscProjectMessages) {

            $DB = new DB();

            //////////////////////////////////
            // delete older records
            $description = __CLASS__;
            $sql = "DELETE FROM activities WHERE description = '$description' AND DATE(created_at) < DATE(NOW() - INTERVAL 10 DAY)";
            $DB->executeQuery($sql);
            //////////////////////////////////

            $lastAddedIdsDB = $DB->get(
                "select activity_id from activities where LOWER(description) = :description ORDER BY id DESC LIMIT " . static::$totalNewPostsToFetch,
                [':description' => strtolower(__CLASS__)]
            );

            $lastAddedIdsDB = $lastAddedIdsDB ?: [];

            $lastAddedIdsDB = array_map(function ($item) {
                return intval($item['activity_id'] ?? '0');
            }, $lastAddedIdsDB);
            //dd($lastAddedIdsDB);

            $messages = array_slice($eteamMiscProjectMessages, 0, static::$totalNewPostsToFetch, true);

            foreach ($messages as $messageId => $messageDetails) {

                if (in_array($messageId, $lastAddedIdsDB, true)) {
                    continue;
                }

                // do not count mr-x
                if (BasecampClassicAPI::$userId == $messageDetails['author-id']) {
                    continue;
                }

                $messageTitle = $messageDetails['title'];

                if (
                    str_starts_with(strtolower(trim($messageTitle)), 'workplan') ||
                    str_starts_with(strtolower(trim($messageTitle)), 'work plan')
                ) {

                    $messageAuthorName = $messageDetails['author-name'];

                    if (DEMO_MODE) {
                        logMessage('DEMO_MODE: ' . __CLASS__ . " : Going to mark attendance for $messageAuthorName");
                        continue;
                    }

                    $result = self::getAttendance($messageAuthorName);

                    if ($result && trim($result) === '') {
                        $attendanceValue = 'P';
                        $messageBody = $messageDetails['body'];

                        if (
                            str_contains(strtolower($messageBody), 'home') ||
                            strtolower($messageAuthorName) === 'Sarfraz Ahmed' ||
                            strtolower($messageAuthorName) === 'Usama Kafeel'
                        ) {
                            $attendanceValue = 'W';
                        }

                        if (date('l') === "Saturday" || date('l') === "Sunday") {
                            $attendanceValue = 'C';
                        }

                        $result = self::markAttendance($messageAuthorName, $attendanceValue);

                        if ($result) {

                            static::markDone($messageId, __CLASS__);
                            logMessage(__CLASS__ . " :  Success", 'success');

                            /*
                        $message = <<<message
                        Dear $messageAuthorName<br><br>
                        I have marked your attendace for today!<br><br>
                        Thanks
                        message;

                        $action = "posts/$messageId/comments.xml";

                        $xmlData = <<<data
                        <comment>
                        <body><![CDATA[$message]]></body>
                        </comment>
                        data;

                        BasecampClassicAPI::postInfo($action, $xmlData);
                         */

                        } else {
                            logMessage(__CLASS__ . " :  Unable to mark attendance for $messageAuthorName", 'error');
                        }

                    } else {
                        logMessage(__CLASS__ . " :  Unable to get attendance value for $messageAuthorName", 'error');
                    }
                }
            }
        }
    }

    private static function sendRequest($employeeName, $isPost = false)
    {
        $url = "https://script.google.com/macros/s/AKfycbxcr08TTVPDXRhPDt4h2DK-pmNeLXYNjfKOcu1Bsb8xp-oFjC9-QA5_CERqsQjC1yZr/exec";

        $payloadArray = [
            "spreadsheetId" => self::SPREAD_SHEET_ID,
            "employeeName" => $employeeName,
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

    private static function markAttendance($employeeName, $value = 'P')
    {
        $result = self::sendRequest($employeeName, true);

        if ($result) {
            if ($result['status'] ?? '' === "success") {
                return true;
            }
        }

        return false;
    }
}
