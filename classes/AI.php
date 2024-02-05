<?php

abstract class AI
{
    protected static string $systemPrompt;

    public static function SetSystemPrompt($prompt): void
    {
        static::$systemPrompt = $prompt;
    }

    public static function getSystemPrompt(): string
    {
        return static::$systemPrompt;
    }

    abstract public static function generateContent($prompt, $useParseDown = false): string;

    public static function generateContentWithRetry($prompt, $useParseDown = false): string
    {
        $retryCount = 0;
        $text = '';

        do {
            $text = static::generateContent($prompt, $useParseDown);

            if (strpos($text, "Error or no response") !== false) {
                $retryCount++;

                if ($retryCount < 3) {
                    sleep(3);
                } else {
                    return "No response after 3 retries, please try again!";
                }
            } else {
                return $text;
            }

        } while ($retryCount < 3);
    }
}
