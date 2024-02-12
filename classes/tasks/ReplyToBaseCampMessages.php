<?php

// Works only for eTeam Misc Tasks Project

class ReplyToBaseCampMessages extends Task
{
    public static function execute()
    {
        logMessage('Running: ' . __CLASS__);

        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();

        if (!$eteamMiscTasksProjectId) {
            logMessage(__CLASS__ . " : Could not get eteam misc tasks project id of basecamp", 'error');
            return;
        }

        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);
        //dd($eteamMiscProjectMessages);

        if (is_array($eteamMiscProjectMessages) && $eteamMiscProjectMessages) {
            $lastFewMessages = array_slice($eteamMiscProjectMessages, 0, 5, true);

            foreach ($lastFewMessages as $messageId => $messageValue) {

                $settingId = 'BC_MESSAGE_' . $messageId;

                $isAlreadyDone = IniReader::get($settingId);

                if ($isAlreadyDone) {
                    // now that main message itself is taken care of, let's see if we need to reply
                    // to any of its comments.
                    static::checkCommentsForReplies($messageId);

                    continue;
                }

                $messageBody = BasecampClassicAPI::getInfo("posts/$messageId.xml");

                if ($messageBody) {
                    $messageBody = (array) $messageBody['body'] ?? '';
                    $messageBody = strip_tags($messageBody[0] ?? '');
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

                    You are helpful assistant and inside basecamp project management platform as user. When someone mentions you
                    by "@mrx", your job then is to answer queries in detailed, polite and very easy to understand manner. You must
                    only reply if there is some sort of question or query, if you think there is nothing to reply then ignore further
                    instructions and just reply with "OK".

                    \n\n[Your reply to $messageBody goes here]

                    PROMPT;

                    GoogleAI::setPrompt($prompt);

                    $response = GoogleAI::GenerateContentWithRetry();

                    // if there is nothing to reply, don't do anything
                    if (strtolower($response) === 'ok') {
                        IniReader::set($settingId, 'true');

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
                            logMessage(__CLASS__ . " :  Basecamp Message Reply Success");
                        } else {
                            logMessage(__CLASS__ . " :  Could not post workplan", 'error');
                        }
                    }
                }

                IniReader::set($settingId, 'true');
            }
        }

    }

    public static function checkCommentsForReplies($messageId)
    {
        //echo "Checking $messageId\n";

        //dd(BasecampClassicAPI::getAllComments($messageId));
    }
}
