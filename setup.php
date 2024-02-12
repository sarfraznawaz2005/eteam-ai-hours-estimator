<?php

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

IniReader::initialize();

// setup our error handler to convert erros into exceptions
set_error_handler(/**
 * @throws ErrorException
 */function ($errorNumber, $errorText, $errorFile, $errorLine) {
    throw new ErrorException($errorText, 0, $errorNumber, $errorFile, $errorLine);
});

function getConfig()
{
    static $config;

    if (empty($config)) {
        return require_once 'config.php';
    }

    return $config;
}

function logMessage($message, $type = 'info', $logFile = 'application.log')
{
    $rootFolder = __DIR__;

    $validTypes = ['info', 'success', 'error'];

    if (!in_array($type, $validTypes)) {
        $type = 'info';
    }

    $fileHandle = fopen($rootFolder . DIRECTORY_SEPARATOR . $logFile, 'a+');

    // Format the message with a timestamp and type
    $formattedMessage = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($type) . '] ' . $message . PHP_EOL;

    fwrite($fileHandle, $formattedMessage);
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
