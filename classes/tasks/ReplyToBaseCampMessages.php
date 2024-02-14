<?php

class ReplyToBaseCampMessages extends Task
{
    protected static $totalNewPostsToFetch = 1;

    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        $projects = BasecampClassicAPI::getAllProjects();

        $DB = DB::getInstance();

        foreach ($projects as $projectId => $projectName) {

            // returns 25 most recent messages by default
            $projectMessages = BasecampClassicAPI::getAllMessages($projectId);
            //dd($projectMessages);

            if (is_array($projectMessages) && $projectMessages) {

                $lastAddedIdsDB = $DB->get(
                    "select activity_id from activities where description = :description ORDER BY id DESC LIMIT " . static::$totalNewPostsToFetch,
                    [':description' => $projectName]
                );

                $lastAddedIdsDB = array_map(function ($item) {
                    return intval($item['activity_id']);
                }, $lastAddedIdsDB);
                //dd($lastAddedIdsDB);

                $messages = array_slice($projectMessages, 0, static::$totalNewPostsToFetch, true);
                //dd($messages);

                foreach ($messages as $messageId => $messageDetails) {

                    if (in_array($messageId, $lastAddedIdsDB, true)) {
                        //echo "Skipping: $messageTitle\n";
                        continue;
                    }

                    if (DEMO_MODE) {
                        logMessage('DEMO_MODE: ' . __CLASS__ . " => ProjectID:$projectId, MessageID:$messageId");
                        continue;
                    }

                    $messageTitle = $messageDetails['title'];
                    $authorId = $messageDetails['author-id'];
                    $messageBody = $messageDetails['body'];

                    // do not reply to self
                    if ((string) $authorId === BasecampClassicAPI::$userId) {
                        continue;
                    }

                    //echo "Processing: $messageTitle\n";
                    //continue;

                    // if title or message body contains mention keyword
                    if (
                        str_contains(strtolower($messageTitle), strtolower(MENTION_TEXT)) ||
                        str_contains(strtolower($messageBody), strtolower(MENTION_TEXT))
                    ) {

                        // if message body is empty, we default to message title
                        $messageBody = $messageBody ?: $messageTitle;

                        $prompt = <<<PROMPT
                        \n\n

                        You are helpful assistant. When someone mentions you by "@mrx", your job then is to answer queries in detailed,
                        polite and very easy to understand manner. You must only reply if there is some sort of question or query, if you
                        think there is nothing to reply then ignore further instructions and just reply with "OK".

                        \n\n[Your reply to $messageBody goes here]

                        PROMPT;

                        GoogleAI::setPrompt($prompt);

                        $response = GoogleAI::GenerateContentWithRetry();

                        // if there is nothing to reply, don't do anything
                        if (strtolower($response) === 'ok') {
                            static::markDone($messageId, $projectName);

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
                                logMessage(__CLASS__ . " :  Basecamp Message Reply Success", 'success');
                            } else {
                                logMessage(__CLASS__ . " :  Could not post workplan", 'danger');
                            }
                        }
                    }

                    static::markDone($messageId, $projectName);
                }
            }
        }

    }
}
