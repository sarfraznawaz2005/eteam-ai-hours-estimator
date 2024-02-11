#!/usr/bin/env php
<?php

require_once './setup.php';
require_once './inbox.php';
require_once './daily.php';

GoogleAI::SetConfig(getConfig());

$functions = [
    //'checkInboxForReplies',
    'getProjectIdea',
];

foreach ($functions as $function) {
    $function();
    sleep(3);
}
