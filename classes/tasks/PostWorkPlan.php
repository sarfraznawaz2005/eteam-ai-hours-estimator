<?php

class PostWorkPlan extends Task
{
    protected static $totalNewPostsToFetch = 3;

    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        $isAlreadyDone = static::isDoneForToday(__CLASS__, __CLASS__);
        

        if ($isAlreadyDone) {
            return;
        }
        
        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();
        

        if (!$eteamMiscTasksProjectId) {
            logMessage('Failed to get the eteam misc tasks project ID. Please verify that the project exists and is accessible.', 'danger');
            return;
        }

        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);

        if (is_array($eteamMiscProjectMessages) && $eteamMiscProjectMessages) {
            
            $messages = array_slice($eteamMiscProjectMessages, 0, static::$totalNewPostsToFetch, true);

            foreach ($messages as $messageId => $messageValue) {

                if (
                    str_starts_with(strtolower(trim($messageValue)), 'workplan') ||
                    str_starts_with(strtolower(trim($messageValue)), 'work plan')
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
                        static::markDone(__CLASS__, __CLASS__);
                    } else {
                        logMessage(__CLASS__ . " :  Could not post workplan", 'danger');
                    }
                }
            }
        }
    }
}
