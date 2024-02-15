#!/usr/bin/env php
<?php

// /usr/local/php80/bin/php-cli -q /home/u754-dxgngyrpjzt9/www/ai.eteamprojects.com/public_html/runner.php

/**
 * TODOs
 * run in parallel
 * whatsapp
 * give reply to basecamp url ???
 * read last post from x project and sent to my email ???
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
    //ReadBaseCampUrlContents::class,
    TestTask::class,
    ReplyToEmails::class,
    PostWorkPlan::class,
    PostProjectIdea::class,
    ReplyToBaseCampMessages::class,
    ReplyToBaseCampComments::class,
    RemindBaseCampCustomers::class,
    RemindMyNameBaseCamp::class,
];

$children = [];

foreach ($tasks as $taskClass) {
    $pid = pcntl_fork();

    if ($pid == -1) {
        die('Could not fork');
    } else if ($pid) {
        // parent process
        $children[] = $pid;
    } else {
        // child process
        $task = new $taskClass();
        $task->execute();
        exit(0);
    }
}

// Wait for all child processes to finish
foreach ($children as $child) {
    pcntl_waitpid($child, $status);
}
