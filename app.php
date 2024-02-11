#!/usr/bin/env php
<?php

require_once './setup.php';
require_once './inbox.php';
require_once './daily.php';

GoogleAI::SetConfig(getConfig());

$tasks = [
    'checkInboxForReplies',
    'postWorkPlan',
    'getProjectIdea',
];

foreach ($tasks as $task) {
    $task();
    sleep(3);
}
