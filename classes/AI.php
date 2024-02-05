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

    abstract public static function generateContentWithRetry($prompt, $useParseDown = false): string;
}
