<?php

class TestTask extends Task
{
    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        $isAlreadyDone = static::isDoneForToday(__CLASS__, __CLASS__);

        if ($isAlreadyDone) {
            return;
        }

        // do something....
        $result = static::markDone(__CLASS__, __CLASS__);

        if ($result) {
            logMessage(__CLASS__ . ' : Marked Done', 'success');
        } else {
            logMessage(__CLASS__ . ' : Unable to mark done', 'danger');
        }

    }
}
