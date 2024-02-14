#!/usr/bin/env php
<?php

// /usr/local/php80/bin/php-cli -q /home/u754-dxgngyrpjzt9/www/ai.eteamprojects.com/public_html/runner.php

/**
 * TODOs
 *
 * reply to comments of posts, don't reply to self
 * give reply to basecamp url
 * read last post from x project and sent to my email
 * remind of un-replied customer messages on basecamp via an email
 * whatsapp
 *
 */

require_once __DIR__ . '/setup.php';

ini_set("memory_limit", "-1");
set_time_limit(0);

if (isLocalhost()) {
    define('DEMO_MODE', true);
} else {
    define('DEMO_MODE', true);
}

$tasks = [
    //TestTask::class,
    //ReplyToEmails::class,
    PostWorkPlan::class,
    //PostProjectIdea::class,
    //ReplyToBaseCampMessages::class,
];

foreach ($tasks as $task) {
    sleep(3);

    $task::execute();
}
