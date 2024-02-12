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

                    GoogleAI::setPrompt("You are helpful assistant. Your job is to answer to user queries in detailed, polite and very easy to understand manner.\n\n[Your reply to $messageBody goes here]");

                    $response = GoogleAI::GenerateContentWithRetry();

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
                            logMessage(__CLASS__ . " :  Success");
                        } else {
                            logMessage(__CLASS__ . " :  Could not post workplan", 'error');
                        }
                    }
                }

                IniReader::set($settingId, 'true');
            }
        }

    }
}
