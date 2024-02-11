<?php

function postWorkPlan()
{
    $isAlreadyDone = IniReader::get(__FUNCTION__);

    if (!$isAlreadyDone) {

        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();
        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);

        if (is_array($eteamMiscProjectMessages) && $eteamMiscProjectMessages) {
            $messsageId = key(array_slice($eteamMiscProjectMessages, 0, 1, true));
            $messageValue = reset($eteamMiscProjectMessages);

            if (
                str_contains(strtolower($messageValue), 'workplan') ||
                str_contains(strtolower($messageValue), 'work plan')
            ) {

                $message = <<<message
                AOA All,<br><br>

                <b>Misc</b>:<br>
                    - Send Today's Project Idea<br>
                    - Code Review of All Developers<br>
                    - Replying to Customer Emails<br>
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
                    logMessage("postWorkPlan: Success");

                    IniReader::set(__FUNCTION__, 'true');
                } else {
                    logMessage("postWorkPlan: Could not post workplan", 'error');
                }
            }
        }
    }
}

function getProjectIdea()
{
    $isAlreadyDone = IniReader::get(__FUNCTION__);

    if (!$isAlreadyDone) {

        GoogleAI::setPrompt(file_get_contents('./tools/idea-generator/prompt.txt') . "\n\nPlease generate a random software product idea based on given instructions.");

        $response = GoogleAI::GenerateContentWithRetry();

        if (!str_contains(strtolower($response), 'no response')) {

            $userIds = BasecampClassicAPI::getAllUsers();

            $notifyPersonsXml = '';

            foreach (array_keys($userIds) as $key) {
                $notifyPersonsXml .= "<notify>$key</notify>\n";
            }

            $postTitle = 'Idea Of The Day - ' . date('d-m-Y');

            $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();

            $action = "projects/$eteamMiscTasksProjectId/posts.xml";

            $xmlData = <<<data
            <request>
                <post>
                    <title>$postTitle</title>
                    <body><![CDATA[$response]]></body>
                </post>
                $notifyPersonsXml
            </request>
            data;

            // send to basecamp
            $response = BasecampClassicAPI::postInfo($action, $xmlData);

            if ($response && $response['code'] === 201) {
                logMessage("getProjectIdea: Success");

                IniReader::set(__FUNCTION__, 'true');
            } else {
                logMessage("getProjectIdea: Could not post workplan", 'error');
            }

        } else {
            logMessage("getProjectIdea: Error or no response", 'error');
        }
    }

}
