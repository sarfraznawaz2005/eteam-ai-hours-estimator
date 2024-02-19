<?php

class ReplyToBaseCampComments extends Task
{
    public static function execute()
    {
        logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            exit(1);
        }

        // we do not run this after this time
        if (!isTimeInRange('11:00PM')) {
            return;
        }

        $projects = BasecampClassicAPI::getAllProjects();

        $DB = new DB();

        foreach ($projects as $projectId => $projectName) {

            // returns 25 most recent messages by default
            $projectMessages = BasecampClassicAPI::getAllMessages($projectId);
            //dd($projectMessages);

            if (is_array($projectMessages) && $projectMessages) {

                // mrx can reply to only "latest" $numMessages messages of the project
                $numMessages = 5;
                $messages = array_slice($projectMessages, 0, $numMessages, true);
                //dd($messages);

                foreach ($messages as $messageId => $messageDetails) {

                    usleep(500000); // 0.5 seconds

                    $messageComments = BasecampClassicAPI::getAllComments($messageId);

                    if (is_array($messageComments) && $messageComments) {

                        $messageTitle = $messageDetails['title'] ?? '';

                        if (!trim($messageTitle)) {
                            continue;
                        }

                        $lastAddedIdsDB = $DB->get(
                            "select activity_id from activities where LOWER(description) = :description ORDER BY id DESC LIMIT 100",
                            [':description' => strtolower($messageTitle)]
                        );

                        $lastAddedIdsDB = $lastAddedIdsDB ?: [];

                        $lastAddedIdsDB = array_map(function ($item) {
                            return intval($item['activity_id'] ?? '0');
                        }, $lastAddedIdsDB);
                        //dd($lastAddedIdsDB);

                        // mrx can reply to only "latest" $numComments comments of the project
                        $numComments = 2;
                        $comments = array_slice($messageComments, 0, $numComments, true);
                        //dd($comments);

                        foreach ($comments as $commentId => $commentDetails) {

                            usleep(500000); // 0.5 seconds

                            if (in_array($commentId, $lastAddedIdsDB)) {
                                continue;
                            }

                            $authorId = $commentDetails['author-id'] ?? '';
                            $commentBody = $commentDetails['body'] ?? '';

                            if (!trim($commentBody)) {
                                continue;
                            }

                            // do not reply to self
                            if ((string) $authorId === BasecampClassicAPI::$userId) {
                                continue;
                            }

                            if (str_contains(strtolower($commentBody), strtolower(MENTION_TEXT))) {

                                if (DEMO_MODE) {
                                    logMessage('DEMO_MODE: ' . __CLASS__ . " => ProjectID:$projectId, MessageID:$messageId, CommentID:$commentId");
                                    continue;
                                }

                                $prompt = <<<PROMPT
                                \n\n

                                You are helpful assistant. When someone mentions you by "@mrx", your job then is to answer queries in detailed,
                                polite and very easy to understand manner.

                                \n\n[Your reply to $commentBody goes here]

                                PROMPT;

                                GoogleAI::setPrompt($prompt);

                                $response = GoogleAI::GenerateContentWithRetry();

                                // if there is nothing to reply, don't do anything
                                if (strtolower(trim(strip_tags($response))) === 'ok') {
                                    static::markDone($commentId, $messageTitle);

                                    continue;
                                }

                                if (!str_contains(strtolower($response), 'no response')) {

                                    $action = "posts/$messageId/comments.xml";

                                    $xmlData = <<<data
                                    <comment>
                                        <body><![CDATA[$response]]></body>
                                    </comment>
                                    data;

                                    // send to basecamp
                                    $response = BasecampClassicAPI::postInfo($action, $xmlData);

                                    if ($response && $response['code'] === 201) {
                                        logMessage(__CLASS__ . " :  Basecamp Comment Reply Success", 'success');
                                    } else {
                                        logMessage(__CLASS__ . " :  Could not post comment reply", 'danger');
                                    }
                                }
                            }

                            static::markDone($commentId, $messageTitle);
                        }
                    }
                }
            }
        }

    }
}
