<?php

date_default_timezone_set('Asia/Karachi');

header('jSGCacheBypass: 1');

require_once __DIR__ . '/vendor/autoload.php';

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

define('MENTION_TEXT', '@mrx');
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

function xSignature()
{
    return <<<body
<br><br>
---<br>
<br>
Mr-X (eTeam AI Bot)<br>
Technical Assistant<br>
<br>
Enterprise Team (eTeam)<br>
607, Level 6,<br>
Ibrahim Trade Towers,<br>
Plot No.1 Block 7 & 8,<br>
MCHS, Main Shahrah-e-Faisal,<br>
Karachi-75400,<br>
Pakistan.<br>
Phone: +(9221) 37120414
body;
}

function runTasksParallel(array $tasks)
{
    $children = [];

    foreach ($tasks as $taskClass) {
        $pid = pcntl_fork();

        if ($pid == -1) {
            // Handle fork failure
            logMessage('Could not fork a child process', 'danger');
            continue; // Optionally, decide how to handle this failure: skip, retry, or abort
        } elseif ($pid) {
            // Parent process
            $children[$pid] = true;
        } else {
            // Child process
            try {
                $task = new $taskClass();
                $task->execute();
            } catch (Exception $e) {
                logMessage('Task execution failed: ' . $e->getMessage(), 'danger');
            }

            exit(0);
        }
    }

    // Non-blocking wait for all child processes to finish
    while (!empty($children)) {
        foreach ($children as $pid => $_) {
            $status = null;
            $res = pcntl_waitpid($pid, $status, WNOHANG);

            if ($res == -1) {
                // Handle waitpid failure
                logMessage("Failed to wait for process $pid", 'danger');
                unset($children[$pid]); // Remove the child from the list to avoid infinite loop
            } elseif ($res > 0) {
                // Child has exited
                unset($children[$pid]);
            }
        }

        usleep(100000); // Sleep for 0.1 seconds to reduce CPU usage
    }
}

function retry(callable $callable, int $maxAttempts = 3)
{
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            call_user_func($callable);
            exit(1); // Exit the function on success
        } catch (Exception $e) {
            if ($attempt === $maxAttempts) {
                logMessage("Error : " . $e->getMessage(), 'danger');
            }
        }
    }
}
