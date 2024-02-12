<?php

$output = '';

// ignore any changes made on online code and sync with pushed version
$output .= shell_exec('git reset --hard' . ' 2>&1');

$output .= shell_exec('git pull origin main' . ' 2>&1');

echo "<pre>$output</pre>";
