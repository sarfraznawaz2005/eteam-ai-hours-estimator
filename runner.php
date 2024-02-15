#!/usr/bin/env php
<?php

// /usr/local/php80/bin/php-cli -q /home/u754-dxgngyrpjzt9/www/ai.eteamprojects.com/public_html/runner.php

/**
 * TODOs
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
    define('DEMO_MODE', true);
}

### order is important
$tasks = [
    //ReadBaseCampUrlContents::class,
    TestTask::class,
    ReplyToEmails::class,
    PostWorkPlan::class,
    PostProjectIdea::class,
    ReplyToBaseCampMessages::class,
    ReplyToBaseCampComments::class,
    RemindBaseCampCustomers::class,
    //RemindMyNameBaseCamp::class,
];

foreach ($tasks as $task) {
    sleep(3);

    $task::execute();
}
