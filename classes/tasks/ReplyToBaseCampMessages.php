<?php

class ReplyToBaseCampMessages extends Task
{
    protected static int $totalNewPostsToFetch = 3; // can reply to that number of most recent messages only

    public static function execute(): void
    {
        logMessage('Running: ' . __CLASS__);

        if (!isLucky()) {
            return;
        }

        if (static::isAlreadyRunning()) {
            return;
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

            if ($projectMessages) {

                $lastAddedIdsDB = $DB->get(
                    "select activity_id from activities where LOWER(description) = :description ORDER BY id DESC LIMIT 100",
                    [':description' => strtolower($projectName)]
                );

                $lastAddedIdsDB = $lastAddedIdsDB ?: [];

                $lastAddedIdsDB = array_map(function ($item) {
                    return intval($item['activity_id'] ?? '0');
                }, $lastAddedIdsDB);
                //dd($lastAddedIdsDB);

                $messages = array_slice($projectMessages, 0, static::$totalNewPostsToFetch, true);
                //dd($messages);

                foreach ($messages as $messageId => $messageDetails) {

                    usleep(500000); // 0.5 seconds

                    if (in_array($messageId, $lastAddedIdsDB)) {
                        continue;
                    }

                    ### if above check still fails and double entry is added,
                    ### then here we can directly check in db for $messageId

                    $messageTitle = $messageDetails['title'] ?? '';
                    $authorId = $messageDetails['author-id'] ?? '';
                    $messageBody = $messageDetails['body'] ?? '';
                    $messageDate = $messageDetails['posted-on'] ?? '';

                    // if message is older than 3 days, we don't reply to it
                    if (strtotime($messageDate) < strtotime('-3 days')) {
                        continue;
                    }

                    // do not reply to self
                    if ((string)$authorId === BasecampClassicAPI::$userId) {
                        continue;
                    }

                    // if title or message body contains mention keyword
                    if (
                        str_contains(strtolower($messageTitle), strtolower(MENTION_TEXT)) ||
                        str_contains(strtolower($messageBody), strtolower(MENTION_TEXT))
                    ) {

                        if (DEMO_MODE) {
                            logMessage('DEMO_MODE: ' . __CLASS__ . " => ProjectID:$projectId, MessageID:$messageId");
                            continue;
                        }

                        // if message body is empty, we default to message title
                        $messageBody = $messageBody ?: $messageTitle;

                        $prompt = <<<PROMPT
                        \n\n

                        You are helpful assistant. When someone mentions you by "@mrx", your job then is to answer queries in detailed,
                        polite and very easy to understand manner.

                        \n\n[Your reply to $messageBody goes here]

                        PROMPT;

                        GoogleAI::setPrompt($prompt);

                        $response = GoogleAI::GenerateContentWithRetry();

                        // if there is nothing to reply, don't do anything
                        if (strtolower(trim(strip_tags($response))) === 'ok') {
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
                                logMessage(__CLASS__ . " :  Could not post message reply", 'danger');
                            }
                        }
                    }

                    static::markDone($messageId, $projectName);
                }
            }
        }

    }
}
