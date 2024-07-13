<?php

use JetBrains\PhpStorm\NoReturn;

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
            break;
        }
    }
}

spl_autoload_register('autoloader');

// set AI model we want to use
AIFactory::setAIModel('google');

//IniReader::initialize();

define('MENTION_TEXT', '@mrx');
define('CONFIG', require_once 'config.php');

// setup our error handler to convert erros into exceptions
set_error_handler(/**
 * @throws ErrorException
 */ function ($errorNumber, $errorText, $errorFile, $errorLine) {
    throw new ErrorException($errorText, 0, $errorNumber, $errorFile, $errorLine);
});

function logMessage($message, $type = 'info', $logFile = 'application.log'): void
{
    $rootFolder = __DIR__;
    $validTypes = ['info', 'success', 'warning', 'danger'];

    if (!in_array($type, $validTypes)) {
        $type = 'info';
    }

    $filePath = $rootFolder . DIRECTORY_SEPARATOR . $logFile;

    // Check if file exists and delete it if it has more than x entries
    if (file_exists($filePath)) {
        $lineCount = count(file($filePath));
        if ($lineCount > 1000) {
            @unlink($filePath);
            logMessage($message, $type);
            return;
        }
    }

    $fileHandle = fopen($filePath, 'a+');

    if ($fileHandle === false) {
        return;
    }

    // Try to acquire an exclusive lock on the file
    if (flock($fileHandle, LOCK_EX | LOCK_NB)) { // The LOCK_NB option makes flock() non-blocking
        $formattedMessage = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($type) . '] ' . $message . PHP_EOL;

        fwrite($fileHandle, $formattedMessage);

        flock($fileHandle, LOCK_UN);
    }

    fclose($fileHandle);
}

#[NoReturn] function dd(...$vars): void
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

function basePath(): string
{
    return __DIR__;
}

function isLocalhost(): bool
{
    return CONFIG['db_pass'] === '';
}

function now(): string
{
    return date("Y-m-d H:i:s");
}

/**
 * @throws Exception
 */
function isDateToday($date): bool
{
    $inputDate = new DateTime($date);
    $today = new DateTime();

    return $inputDate->format('Y-m-d') === $today->format('Y-m-d');
}

// compares time between 6am and the given time
function isTimeInRange($endTimeAmPm): bool
{
    date_default_timezone_set('Asia/Karachi');

    $currentTime = strtotime(date('Y-m-d H:i'));

    $startTime = strtotime('today 6:00 AM');

    $endTime = strtotime("today " . $endTimeAmPm) + 59;

    if ($currentTime >= $startTime && $currentTime <= $endTime) {
        return true;
    }

    return false;
}

function xSignature(): string
{
    return <<<body
<br><br>
Thanks<br>
---<br>
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

function runTasksParallel(array $tasks): void
{
    $children = [];

    foreach ($tasks as $taskClass) {
        $pid = pcntl_fork();

        if ($pid == -1) {
            logMessage('Could not fork a child process', 'danger');
            // Optionally, decide how to handle this failure: skip, retry, or abort
        } elseif ($pid) {
            // Parent process
            $children[$pid] = true;
        } else {
            // Child process
            try {
                $taskClass::execute();
            } catch (Exception $e) {
                logMessage($taskClass . ' - execution failed: ' . $e->getMessage(), 'danger');
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

function retry(callable $callable, int $maxAttempts = 3): void
{
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            usleep(100000); // Sleep for 0.1 seconds to reduce CPU usage
            call_user_func($callable);
            return; // Exit the function on success
        } catch (Exception $e) {
            if ($attempt === $maxAttempts) {
                logMessage("Error : " . $e->getMessage(), 'danger');
            }
        }
    }
}

function isLucky($max = 3): bool
{
    return 1 === rand(0, $max);
}