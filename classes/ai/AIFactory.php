<?php

class AIFactory
{
    private static string $aiModel;

    public static function setAIModel($model): void
    {
        /* @noinspection ALL */
        self::$aiModel = match ($model) {
            'google' => 'GoogleAI',
            'openai' => 'OpenAI',
            default => throw new Exception('Invalid AI model specified.'),
        };
    }

    public static function getAIModel(): AI
    {
        $aiModelClass = self::$aiModel ?? 'GoogleAI';
        return new $aiModelClass();
    }
}
