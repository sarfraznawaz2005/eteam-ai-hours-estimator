#!/usr/bin/env php
<?php

/**
 * TODOs
 *
 * Reply to comments of posts
 * Remind of un-replied customer messages on basecamp via an email
 *
 *
 */

require_once __DIR__ . '/setup.php';

GoogleAI::SetConfig(getConfig());

$tasks = [
    ReplyToEmails::class,
    PostWorkPlan::class,
    PostProjectIdea::class,
    ReplyToBaseCampMessages::class,
];

foreach ($tasks as $task) {

    if (IniReader::isLocked()) {
        continue;
    }

    sleep(3);

    $task::execute();
}
