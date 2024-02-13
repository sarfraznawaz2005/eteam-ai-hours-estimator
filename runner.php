#!/usr/bin/env php
<?php

// /usr/local/php80/bin/php-cli -q /home/u754-dxgngyrpjzt9/www/ai.eteamprojects.com/public_html/runner.php

/**
 * TODOs
 *
 * reply to comments of posts, don't reply to self
 * remind of un-replied customer messages on basecamp via an email
 * give reply to basecamp url
 * whatsapp
 *
 */

require_once __DIR__ . '/setup.php';


$tasks = [
    //TestTask::class,
    //ReplyToEmails::class,
    //PostWorkPlan::class,
    //PostProjectIdea::class,
    ReplyToBaseCampMessages::class,
];

if (isLocalhost()) {
    //$tasks = array_slice($tasks, 0, 1);
}

foreach ($tasks as $task) {
    sleep(3);

    $task::execute();
}
