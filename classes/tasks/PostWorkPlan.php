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

            $DB = DB::getInstance();

            $lastAddedIdsDB = $DB->get(
                "select activity_id from activities where description = :description ORDER BY id DESC LIMIT " . static::$totalNewPostsToFetch,
                [':description' => __CLASS__]
            );

            $lastAddedIdsDB = array_map(function ($item) {
                return intval($item['activity_id']);
            }, $lastAddedIdsDB);
            //dd($lastAddedIdsDB);

            $messages = array_slice($eteamMiscProjectMessages, 0, static::$totalNewPostsToFetch, true);

            foreach ($messages as $messageId => $messageDetails) {

                if (in_array($messageId, $lastAddedIdsDB, true)) {
                    continue;
                }

                if (DEMO_MODE) {
                    logMessage('DEMO_MODE: ' . __CLASS__ . " => MessageID:$messageId, ProjectID:$eteamMiscTasksProjectId");
                    continue;
                }

                $messageTitle = $messageDetails['title'];

                if (
                    str_starts_with(strtolower(trim($messageTitle)), 'workplan') ||
                    str_starts_with(strtolower(trim($messageTitle)), 'work plan')
                ) {

                    $message = <<<message
                    AOA,<br><br>

                    - Post Project Idea<br>
                    - Code Reviews<br>
                    - Email Communication<br>
                    - Basecamp Communication<br>
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
                    } else {
                        logMessage(__CLASS__ . " :  Could not post workplan", 'danger');
                    }
                }
            }
        }
    }
}
