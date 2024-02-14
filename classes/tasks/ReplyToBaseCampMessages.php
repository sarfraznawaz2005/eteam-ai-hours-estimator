<?php

class ReplyToBaseCampMessages extends Task
{
    protected static $totalNewPostsToFetch = 3;

    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();

        if (!$eteamMiscTasksProjectId) {
            logMessage('Failed to get the eteam misc tasks project ID. Please verify that the project exists and is accessible.', 'danger');
            return;
        }

        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);
        //dd($eteamMiscProjectMessages);

        if (is_array($eteamMiscProjectMessages) && $eteamMiscProjectMessages) {

            $DB = DB::getInstance();

            $lastFewMessagesIdsDB = $DB->get(
                "select activity_id from activities where description = :description ORDER BY id DESC LIMIT " . static::$totalNewPostsToFetch,
                [':description' => 'Basecamp Messages']
            );

            $lastFewMessagesIdsDB = array_map(function ($item) {
                return intval($item['activity_id']);
            }, $lastFewMessagesIdsDB);
            //dd($lastFewMessagesIdsDB);

            $messages = array_slice($eteamMiscProjectMessages, 0, static::$totalNewPostsToFetch, true);

            foreach ($messages as $messageId => $messageValue) {

                if (in_array($messageId, $lastFewMessagesIdsDB, true)) {
                    continue;
                }

                $authorId = '';
                $messageBody = BasecampClassicAPI::getInfo("posts/$messageId.xml");

                if ($messageBody) {
                    $post = (array) $messageBody;
                    $messageBody = $post['body'] ?? '';
                    $messageBody = strip_tags($messageBody[0] ?? '');
                    $authorId = $post['author-id'] ?? '';
                }

                // do not reply to self
                if ((string) $authorId === BasecampClassicAPI::$userId) {
                    continue;
                }

                // if title or message body contains mention keyword
                if (
                    str_contains(strtolower($messageValue), strtolower(MENTION_TEXT)) ||
                    str_contains(strtolower($messageBody), strtolower(MENTION_TEXT))
                ) {

                    // if message body is empty, we default to message title
                    $messageBody = $messageBody ?: $messageValue;

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
                        static::markDone($messageId, 'Basecamp Messages');

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

                static::markDone($messageId, 'Basecamp Messages');
            }
        }

    }
}
