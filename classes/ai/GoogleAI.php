<?php

// https://ai.google.dev/models/gemini

class GoogleAI extends AI
{
    public static function setPrompt(string $prompt): void
    {
        $facts = <<< facts
        Here are some facts you should know:

        - Your name is "Mr-X", created by "Sarfraz Ahmed" at eTeam.
        - Your age is 30 years.
        - Your work as Technical Assistant at eTeam.
        - You jointed eTeam on 12 Feb 2024.
        - People at eTeam only understand and speak English language.
        - eTeam is software technology company founded in 2007 specialized in web and mobile apps.
        - eTeam motto or mission is "We see work as source of learning, enjoyment, financial gain and an opportunity to make a positive impact on the world including ourselves."
        - Values of eTeam are: "Build trust with honesty and by taking responsibility", "Help clients achieve maximum for their businesses", "Nurture talent and let them innovate", "Build a team of thinking decision makers", "Grow ideas into products"

        \n\n
        facts;

        static::$prompts[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $facts . $prompt],
            ],
        ];
    }

    public static function generateContent($useParseDown = true): string
    {
        $apiKey = static::$config['GOOGLE_API_KEY'];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=$apiKey";

        // since currently we cannot find a way to send multiple prompts to api at once
        $prompt = end(static::$prompts);

        $data = [
            'contents' => [$prompt],
            /*
            'safetySettings' => [
            [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_ONLY_HIGH',
            ],
            ],
             */
            'generationConfig' => [
                //'stopSequences' => [
                //    'Title',
                //],
                'maxOutputTokens' => 4096,
                //'temperature' => 0.5,
                //'topP' => 0.5,
                //'topK' => 20,
            ],

        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || empty($response)) {
            return "Error or no response, please try again!";
        } else {

            $response = json_decode($response, true);
            $text = '';

            foreach ($response['candidates'] as $candidate) {
                if (!isset($candidate['content'])) {
                    return "No response, please try again!";
                }

                foreach ($candidate['content']['parts'] as $part) {
                    $text .= $part['text'] . "\n";
                }
            }

            if ($useParseDown) {
                Parsedown::instance()->setSafeMode(false);
                Parsedown::instance()->setBreaksEnabled(true);
                Parsedown::instance()->setMarkupEscaped(true);
                Parsedown::instance()->setUrlsLinked(true);

                return Parsedown::instance()->text($text);
            }

            return mb_convert_encoding($text, 'UTF-8');
        }
    }
}