#!/usr/bin/env php
<?php

// /usr/local/php80/bin/php-cli -q /home/u754-dxgngyrpjzt9/www/ai.eteamprojects.com/public_html/runner.php

/**
 * TODOs
 * python
 * integrate in IDE, understand codebase
 * whatsapp
 * give reply to basecamp url ???
 * read last post from x project and sent to my email ???
 *
 */

date_default_timezone_set('Asia/Karachi');

require_once __DIR__ . '/setup.php';

ini_set("memory_limit", "-1");
set_time_limit(0);

if (isLocalhost()) {
    define('DEMO_MODE', true);
} else {
    define('DEMO_MODE', false);
}

$tasks = [
    //ReadBaseCampUrlContents::class,
    TestTask::class,
    PostWorkPlan::class,
    MarkAttendance::class,
    PostProjectIdea::class,
    ReplyToEmails::class,
    ReplyToBaseCampMessages::class,
    ReplyToBaseCampComments::class, // taking too much time making basecamp go down temporarily
    RemindBaseCampCustomers::class,
    RemindHours::class,
];

if (function_exists('pcntl_fork')) {
    runTasksParallel($tasks);
} else {
    foreach ($tasks as $task) {
        usleep(300000); // 0.3 seconds

        /**
         * @var Task $task
         */
        $task::execute();
    }
}
