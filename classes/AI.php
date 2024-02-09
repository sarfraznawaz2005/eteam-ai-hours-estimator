<?php

abstract class AI
{
    protected static array $config = [];

    protected static array $prompts = [];

    abstract public static function setPrompt(string $prompt): void;

    abstract public static function generateContent(bool $useParseDown = true): string;

    public static function setConfig(array $config): void
    {
        static::$config = $config;
    }

    public static function generateContentWithRetry(bool $useParseDown = true, $retryCount = 3, $sleepInterval = 3): string
    {

        //print_r(static::$prompts);

        do {
            $text = static::generateContent($useParseDown);

            if (str_contains($text, "Error or no response")) {
                $retryCount++;

                if ($retryCount < $sleepInterval) {
                    sleep($sleepInterval);
                } else {
                    return "No response after $sleepInterval retries, please try again!";
                }
            } else {
                return $text;
            }

        } while ($retryCount < $sleepInterval);

        return $text;
    }
}
