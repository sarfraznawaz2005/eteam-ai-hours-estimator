#!/usr/bin/env php
<?php

/**
 * TODOs
 * 
 * Remind of un-replied customer messages on basecamp via an email
 * Does not seem to add cc people
 * 
 * 
 */

require_once __DIR__ . '/setup.php';

GoogleAI::SetConfig(getConfig());

$tasks = [
    CheckInboxForReplies::class,
    //PostWorkPlan::class,
    //PostProjectIdea::class,
    ReplyToBaseCampMessages::class,
];

foreach ($tasks as $task) {
    $task::execute();

    sleep(3);
}
