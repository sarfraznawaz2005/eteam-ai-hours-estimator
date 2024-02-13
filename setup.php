<?php

ini_set("memory_limit", "-1");
set_time_limit(0);

header('jSGCacheBypass: 1');

require_once __DIR__ . '/vendor/autoload.php';

define('MENTION_TEXT', '@mrx');

date_default_timezone_set('Asia/Karachi');

function autoloader($className): void
{
    $directories = [
        __DIR__ . '/classes/',
        __DIR__ . '/classes/ai/',
        __DIR__ . '/classes/tasks/',
    ];

    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';

        if (file_exists($file)) {
            require_once $file;
            break; // Stop the loop once the file is found and required
        }
    }
}

spl_autoload_register('autoloader');

//IniReader::initialize();

define('CONFIG', require_once 'config.php');

// setup our error handler to convert erros into exceptions
set_error_handler(/**
 * @throws ErrorException
 */function ($errorNumber, $errorText, $errorFile, $errorLine) {
    throw new ErrorException($errorText, 0, $errorNumber, $errorFile, $errorLine);
});

function logMessage($message, $type = 'info', $logFile = 'application.log')
{
    $rootFolder = __DIR__;
    $validTypes = ['info', 'success', 'warning', 'danger'];

    if (!in_array($type, $validTypes)) {
        $type = 'info';
    }

    $filePath = $rootFolder . DIRECTORY_SEPARATOR . $logFile;

    // Open the file depending on its size: 'w' mode if it's larger than 1MB, 'a+' otherwise
    if (file_exists($filePath) && filesize($filePath) > 1048576) {
        $fileHandle = fopen($filePath, 'w');
    } else {
        $fileHandle = fopen($filePath, 'a+');
    }

    if ($fileHandle === false) {
        // Unable to open the file, possibly due to permissions or other errors
        return;
    }

    // Try to acquire an exclusive lock on the file
    if (flock($fileHandle, LOCK_EX | LOCK_NB)) { // The LOCK_NB option makes flock() non-blocking
        $formattedMessage = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($type) . '] ' . $message . PHP_EOL;

        fwrite($fileHandle, $formattedMessage);

        flock($fileHandle, LOCK_UN);
    } else {
        // The file is busy or locked by another process, so we skip writing to it
    }

    fclose($fileHandle);
}

function dd(...$vars)
{
    $isCli = php_sapi_name() === 'cli';

    foreach ($vars as $var) {
        if (!$isCli) {
            echo '<pre>';
        }

        var_dump($var);

        if (!$isCli) {
            echo '</pre>';
        }
    }

    die(1);
}

function basePath()
{
    return __DIR__;
}

function isLocalhost()
{
    return CONFIG['db_pass'] === '';
}

function now()
{
    return date("Y-m-d H:i:s");
}

//dd(BasecampClassicAPI::getAllProjects());
