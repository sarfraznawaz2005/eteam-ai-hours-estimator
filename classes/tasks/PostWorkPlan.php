<?php

class PostWorkPlan extends Task
{
    public static function execute()
    {
        logMessage('Running: ' . __CLASS__);

        $isAlreadyDone = IniReader::get(__CLASS__);

        if ($isAlreadyDone) {
            return;
        }

        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();

        if (!$eteamMiscTasksProjectId) {
            logMessage(__CLASS__ . " : Could not get eteam misc tasks project id of basecamp", 'error');
            return;
        }

        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);

        if (is_array($eteamMiscProjectMessages) && $eteamMiscProjectMessages) {
            $messsageId = key(array_slice($eteamMiscProjectMessages, 0, 1, true));
            $messageValue = reset($eteamMiscProjectMessages);

            if (
                str_contains(strtolower($messageValue), 'workplan') ||
                str_contains(strtolower($messageValue), 'work plan')
            ) {

                $message = <<<message
                AOA,<br><br>

                <b>Misc</b>:<br>
                    - Send Today's Project Idea<br>
                    - Code Review<br>
                    - Replying to Customer Emails<br>
                    - Replying to Basecamp Messages<br>
                    - Estimate Projects<br>
                    - Create System Plan For New Projects<br>
                    - Provide Database Support<br>
                    - SEO Optimizations<br>
                    - Coordinate with Team<br>
                    - Etc
                message;

                GoogleAI::setPrompt("Please provide a inspirational quote tailored to our software engineering company. This inspirational quote should boost the morale of our team.");

                $response = GoogleAI::GenerateContentWithRetry();

                if (!str_contains(strtolower($response), 'no response')) {
                    $message .= <<<message
                            <br><br><b>Inspirational Quote Of The Day:</b><br>

                            $response
                        message;
                }

                $action = "posts/$messsageId/comments.xml";

                $xmlData = <<<data
                <comment>
                    <body><![CDATA[$message]]></body>
                </comment>
                data;

                // send to basecamp
                $response = BasecampClassicAPI::postInfo($action, $xmlData);

                if ($response && $response['code'] === 201) {
                    logMessage(__CLASS__ . " :  Success");

                    IniReader::set(__CLASS__, 'true');
                } else {
                    logMessage(__CLASS__ . " :  Could not post workplan", 'error');
                }
            }
        }
    }
}
