<?php

class PostWorkPlan extends Task
{
    protected static $totalNewPostsToFetch = 1;

    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();
        //dd($eteamMiscTasksProjectId);

        if (!$eteamMiscTasksProjectId) {
            logMessage('Failed to get the eteam misc tasks project ID. Please verify that the project exists and is accessible.', 'danger');
            return;
        }

        // returns 25 most recent messages by default
        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);
        //dd($eteamMiscProjectMessages);

        if (is_array($eteamMiscProjectMessages) && $eteamMiscProjectMessages) {

            $DB = new DB();

            //////////////////////////////////
            // delete older records
            $description = __CLASS__;
            $sql = "DELETE FROM activities WHERE description = '$description' AND created_at < NOW() - INTERVAL 1 DAY";
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

                $messageTitle = $messageDetails['title'];

                if (
                    str_starts_with(strtolower(trim($messageTitle)), 'workplan') ||
                    str_starts_with(strtolower(trim($messageTitle)), 'work plan')
                ) {

                    if (DEMO_MODE) {
                        logMessage('DEMO_MODE: ' . __CLASS__ . " => ProjectID:$eteamMiscTasksProjectId, MessageID:$messageId");
                        continue;
                    }

                    $message = <<<message
                    AOA,<br><br>

                    - Post Project Ideas<br>
                    - Code Reviews<br>
                    - Email Communication<br>
                    - Basecamp Communication<br>
                    - Send BaseCamp Customer Reminders<br>
                    - Provide AI Tools Services<br>
                    - Coordinate with Team<br>
                    - etc
                    message;

                    GoogleAI::setPrompt("Please provide a inspirational quote tailored to our software engineering company. This inspirational quote should boost the morale of our team.");

                    $response = GoogleAI::GenerateContentWithRetry();

                    if (!str_contains(strtolower($response), 'no response')) {
                        $message .= <<<message
                            <br><br><b>Inspirational Quote Of The Day:</b><br>

                            $response
                        message;
                    }

                    $action = "posts/$messageId/comments.xml";

                    $xmlData = <<<data
                    <comment>
                        <body><![CDATA[$message]]></body>
                    </comment>
                    data;

                    // send to basecamp
                    $response = BasecampClassicAPI::postInfo($action, $xmlData);

                    if ($response && $response['code'] === 201) {
                        static::markDone($messageId, __CLASS__);
                        logMessage(__CLASS__ . " :  Workplan Post Success", 'success');
                    } else {
                        logMessage(__CLASS__ . " :  Could not post workplan", 'danger');
                    }
                }
            }
        }
    }
}
