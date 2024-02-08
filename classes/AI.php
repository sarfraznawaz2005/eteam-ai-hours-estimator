<?php

abstract class AI
{
    protected static array $config;

    protected static string $systemPrompt;

    public static function SetConfig(array $config): void
    {
        static::$config = $config;
    }

    public static function SetSystemPrompt(string $prompt): void
    {
        static::$systemPrompt = $prompt;
    }

    public static function getSystemPrompt(): string
    {
        return static::$systemPrompt;
    }

    abstract public static function generateContent(string $prompt, bool $useParseDown = true): string;

    public static function generateContentWithRetry(string $prompt, bool $useParseDown = true, $retryCount = 3, $sleepInterval = 3): string
    {
        do {
            $text = static::generateContent($prompt, $useParseDown);

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

    public static function generateMultipleContents(array $prompts, string $method = 'generateContent', bool $useParseDown = true, int $retryCount = 3, $sleepInterval = 3): string
    {
        $response = '';

        foreach ($prompts as $prompt) {

            GoogleAI::SetSystemPrompt($prompt['system_prompt']);

            if ($method === 'generateContent') {
                $response .= static::$method($prompt['user_prompt'], $useParseDown);
            } else {
                $response .= static::$method($prompt['user_prompt'], $useParseDown, $retryCount, $sleepInterval);
            }
        }

        return $response;
    }
}
