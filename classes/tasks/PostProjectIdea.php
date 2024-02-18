<?php

class PostProjectIdea extends Task
{
    public static function execute()
    {
        logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            exit(1);
        }

        // only on Mondays
        if (date('l') !== "Monday") {
            return;
        }

        if (DEMO_MODE) {
            logMessage('DEMO_MODE: ' . __CLASS__);
            return;
        }
        
        // we do not run this after this time
        if (!isTimeInRange('3:00PM')) {
            return;
        }

        $isAlreadyDone = static::isDoneForToday(__CLASS__, __CLASS__);

        if ($isAlreadyDone) {
            return;
        }

        $eteamKnowledgeSharingProjectId = BasecampClassicAPI::getEteamKnowledgeSharingProjectId();

        if (!$eteamKnowledgeSharingProjectId) {
            logMessage('Failed to get the eteam knowledge sharing project ID. Please verify that the project exists and is accessible.', 'danger');
            return;
        }

        GoogleAI::setPrompt(file_get_contents(basePath() . '/tools/idea-generator/prompt.txt') . "\n\nPlease generate a random software product idea based on given instructions. Your answer must not go more than 3 indentations.");

        $response = GoogleAI::GenerateContentWithRetry();

        if (!str_contains(strtolower($response), 'no response')) {

            $userIds = BasecampClassicAPI::getAllUsers();

            $notifyPersonsXml = '';

            foreach (array_keys($userIds) as $key) {
                $notifyPersonsXml .= "<notify>$key</notify>\n";
            }

            $postTitle = 'Idea Of The Day - ' . date('d-m-Y');

            if (preg_match('/Idea Name: (.*?)\n/i', strip_tags($response), $matches)) {
                $ideaName = $matches[1] ?? '';

                if (trim($ideaName)) {
                    $postTitle .= " [$ideaName]";
                }
            }

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
                static::markDone(__CLASS__, __CLASS__);
                logMessage(__CLASS__ . " :  Post Daily Idea Success", 'success');
            } else {
                logMessage(__CLASS__ . " : Could not post workplan", 'danger');
            }

        } else {
            logMessage(__CLASS__ . " : Error or no response", 'danger');
        }

    }
}
