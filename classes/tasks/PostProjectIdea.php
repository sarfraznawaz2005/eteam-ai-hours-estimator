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

        if (!$isAlreadyDone) {

            $eteamKnowledgeSharingProjectId = BasecampClassicAPI::getEteamKnowledgeSharingProjectId();

            if (!$eteamKnowledgeSharingProjectId) {
                logMessage(__CLASS__ . " : Could not get eteam knowledge sharing project id of basecamp", 'error');
                return;
            }

            GoogleAI::setPrompt(file_get_contents('./tools/idea-generator/prompt.txt') . "\n\nPlease generate a random software product idea based on given instructions.");

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
                    logMessage(__CLASS__ . " : Success");

                    IniReader::set(__CLASS__, 'true');
                } else {
                    logMessage(__CLASS__ . " : Could not post workplan", 'error');
                }

            } else {
                logMessage(__CLASS__ . " : Error or no response", 'error');
            }
        }

    }
}