<?php

class GoogleAI extends AI
{
    public static function generateContent($prompt, $useParseDown = true): string
    {
        $apiKey = static::$config['GOOGLE_API_KEY'];

        // https://ai.google.dev/models/gemini
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=$apiKey";

        $requestPrompt = static::getSystemPrompt() . $prompt;
        //return $requestPrompt;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $requestPrompt,
                        ],
                    ],
                ],
            ],
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
        'maxOutputTokens' => 4048,
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
