#!/usr/bin/env php
<?php

// /usr/local/php80/bin/php-cli -q /home/u754-dxgngyrpjzt9/www/ai.eteamprojects.com/public_html/runner.php

/**
 * TODOs
 *
 * db
 * reply to comments of posts
 * remind of un-replied customer messages on basecamp via an email
 * give reply to basecamp url
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
