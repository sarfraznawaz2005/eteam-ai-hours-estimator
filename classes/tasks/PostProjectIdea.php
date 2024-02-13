<?php

class PostProjectIdea extends Task
{
    public static function execute()
    {
        logMessage('Running: ' . __CLASS__);

        $isWorkplanPosted = IniReader::get(PostWorkPlan::class);

        // we only send project idea after we have posted workplan
        if (!$isWorkplanPosted) {
            return;
        }

        $isAlreadyDone = IniReader::get(__CLASS__);

        if ($isAlreadyDone) {
            return;
        }

        $eteamKnowledgeSharingProjectId = BasecampClassicAPI::getEteamKnowledgeSharingProjectId();

        if (!$eteamKnowledgeSharingProjectId) {
            logMessage('Failed to get the eteam knowledge sharing project ID. Please verify that the project exists and is accessible.', 'danger');
            return;
        }

        GoogleAI::setPrompt(file_get_contents(__DIR__ . '/../../tools/idea-generator/prompt.txt') . "\n\nPlease generate a random software product idea based on given instructions. Your answer must not go more than 3 indentations.");

        $response = GoogleAI::GenerateContentWithRetry();

        if (!str_contains(strtolower($response), 'no response')) {

            $userIds = BasecampClassicAPI::getAllUsers();

            $notifyPersonsXml = '';

            foreach (array_keys($userIds) as $key) {
                $notifyPersonsXml .= "<notify>$key</notify>\n";
            }

            $postTitle = 'Idea Of The Day - ' . date('d-m-Y');

            $action = "projects/$eteamKnowledgeSharingProjectId/posts.xml";

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
                logMessage(__CLASS__ . " : Success", 'success');

                IniReader::set(__CLASS__, 'true');
            } else {
                logMessage(__CLASS__ . " : Could not post workplan", 'danger');
            }

        } else {
            logMessage(__CLASS__ . " : Error or no response", 'danger');
        }

    }
}
