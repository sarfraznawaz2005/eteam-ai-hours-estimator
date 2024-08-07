<?php

class CodingTipOfTheDay extends Task
{
    public static function execute(): void
    {
        logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            return;
        }

        if (DEMO_MODE) {
            logMessage('DEMO_MODE: ' . __CLASS__);
            return;
        }

        // we do not run this after this time
        if (!isTimeInRange('1:00PM')) {
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

        $aiModel = AIFactory::getAIModel();

        $aiModel::setPrompt("\n\nPlease generate a detailed and useful tip. It should be based on PHP, laravel, javascript, reactjs, devops, git, css, ai, project management, db, web development, mobile development, best practices, performance, security, tech tools, or some other web technology including any new tech we need to learn.");

        $response = $aiModel::GenerateContentWithRetry();

        //$response = preg_replace('/\n{2,}/', "\n", $response);
        //$response = preg_replace('/\n/', "\n\n", $response);
        //$response = "<pre>$response</pre>";
        //$response = strip_tags($response);

        if (!str_contains(strtolower($response), 'no response')) {

            $userIds = BasecampClassicAPI::getAllUsers();

            $notifyPersonsXml = '';

            foreach (array_keys($userIds) as $key) {
                $notifyPersonsXml .= "<notify>$key</notify>\n";
            }

            $postTitle = 'Tip of the Day';

            if (preg_match('/Tip: (.*?)\n/i', strip_tags($response), $matches)) {
                $ideaName = $matches[1] ?? '';

                if (trim($ideaName)) {
                    $postTitle .= " - [$ideaName]";
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
                logMessage(__CLASS__ . " :  Post Tip Success", 'success');
            } else {
                logMessage(__CLASS__ . " : Could not post tip", 'danger');
            }
        } else {
            logMessage(__CLASS__ . " : Error or no response", 'danger');
        }
    }
}
