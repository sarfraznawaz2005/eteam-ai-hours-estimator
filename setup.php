<?php

function autoloader($className)
{
    $file = __DIR__ . '/classes/' . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
}

spl_autoload_register('autoloader');

// setup our error handler to convert erros into exceptions
set_error_handler(function ($errorNumber, $errorText, $errorFile, $errorLine) {
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
