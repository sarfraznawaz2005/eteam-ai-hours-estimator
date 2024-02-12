<?php

// ignore any changes made on online code and sync with pushed version
shell_exec('git reset --hard');

$output = shell_exec('git pull origin main' . ' 2>&1');

echo $output;
