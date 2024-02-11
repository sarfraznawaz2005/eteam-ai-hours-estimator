#!/usr/bin/env php
<?php

require_once './setup.php';
require_once './tasks.php';

GoogleAI::SetConfig(getConfig());

$tasks = [
    'checkInboxForReplies',
    'postWorkPlan',
    'postProjectIdea',
    'replyToBaseCampMessages',
];

foreach ($tasks as $task) {
    $task();

    sleep(3);
}
