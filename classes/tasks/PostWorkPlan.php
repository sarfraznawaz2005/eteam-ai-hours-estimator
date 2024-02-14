<?php

class PostWorkPlan extends Task
{
    protected static $totalNewPostsToFetch = 3;

    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        $isAlreadyDone = static::isDoneForToday(__CLASS__, __CLASS__);
        

        if ($isAlreadyDone) {
            return;
        }
        
        $eteamMiscTasksProjectId = BasecampClassicAPI::getEteamMiscTasksProjectId();
        

        if (!$eteamMiscTasksProjectId) {
            logMessage('Failed to get the eteam misc tasks project ID. Please verify that the project exists and is accessible.', 'danger');
            return;
        }

        $eteamMiscProjectMessages = BasecampClassicAPI::getAllMessages($eteamMiscTasksProjectId);

        if (is_array($eteamMiscProjectMessages) && $eteamMiscProjectMessages) {
            
            $messages = array_slice($eteamMiscProjectMessages, 0, static::$totalNewPostsToFetch, true);

            foreach ($messages as $messageId => $messageValue) {

                if (
                    str_starts_with(strtolower(trim($messageValue)), 'workplan') ||
                    str_starts_with(strtolower(trim($messageValue)), 'work plan')
                ) {

                    logMessage(__CLASS__ . " :  POSTED WORKPLAN", 'success');

                    static::markDone(__CLASS__, __CLASS__);
                }
            }
        }
    }
}
