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

    abstract public static function generateContent($prompt, $useParseDown = true): string;

    public static function generateContentWithRetry($prompt, $useParseDown = true): string
    {
        $retryCount = 0;

        do {
            $text = static::generateContent($prompt, $useParseDown);

            if (str_contains($text, "Error or no response")) {
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

        return $text;
    }
}
