#!/usr/bin/env php
<?php

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
