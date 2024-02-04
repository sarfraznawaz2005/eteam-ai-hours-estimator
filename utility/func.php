<?php

require_once 'Parsedown.php';

function generateContent($prompt, $useParseDown = false): string
{
    $config = require_once '../../config.php';

    $apiKey = $config['GOOGLE_API_KEY'];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=$apiKey";

    $requestPrompt = getSystemPrompt() . $prompt;
    //echo $requestPrompt;exit;

    // TODO: Customize params such as top_k, temparature, etc for ideal results.

    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $requestPrompt
                    ]
                ]
            ]
        ]
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

        // calculate total estimate manually since AI is weak in maths
        $pattern = '/\d+(?= hours)/';
        preg_match_all($pattern, $text, $matches);

        $total = array_sum($matches[0]);

        $text = $text . "<hr><strong>Total Rough Estimate: $total</strong>";

        if ($useParseDown) {
            $pd = new Parsedown();

            $pd->setSafeMode(true);
            $pd->setBreaksEnabled(true);
            $pd->setMarkupEscaped(true);
            $pd->setUrlsLinked(true);
    
            return $pd->text($text);
        }

        return $text;
    }
}

function generateContentWithRetry($prompt, $useParseDown = false): string
{
    $retryCount = 0;
    $text = '';

    do {
        $text = generateContent($prompt, $useParseDown);

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

function getSystemPrompt() {
    return <<<'PROMPT'

    Act as senior software architect. If I give you project description, your job is give me estimate in hours.
    
    Rules you must follow:
    
    - You must not give random estimate instead you must give realistic estimate by breakdown and identifying each requirement and feature. Your estimates represent a human who is average software engineer.
    - Analyze the key components and tasks and give their estimate
    - You must always reply in consistent style.
    - Don't put "<ins>" or "</ins>" in your answers.
    - You will not calculate total hours.
    - Your estimates must never be zero.
    - You must indent headings automatically and smartly.
    - You may search over the internet if you like.
    
    Your output must be exactly like this:
    
    - Designing
        - Sketches & Wireframes (00 hours)
        - Photoshop and HTMLs (00 hours)
        - Responsive Design (00 hours)
    - Development:
            - Project Setup
                - Set up development environment (00 hours)
                - Install necessary framework & libraries (00 hours)
            - Database Design
                - Design the database schema (00 hours)
                - Define relationships between entities (00 hours)
                - Implement necessary indexes for performance (00 hours)
                - Setup necessary database tooling (00 hours)
            - Features
                    - User Authentication & Authorization (00 hours)
                    
                    - [identify all features and further break it down in tasks if needed with given project description]
    
    - Security Considerations
    
        - [identify and further break it down in tasks if needed with given project description]
        
    - Testing (00 hours)
    - Deployment (00 hours)
    - Communication (00 hours)
    - Documentation (00 hours)

    [identify any further features or ideas that can give more value to project with given project description. If you have something to suggest, put it under "<strong>***Nice To Have Features*** 😎</strong>".]
    
    --------------------------------------------------------
    
    PROMPT;
}

function getWaitingImage() {
    $gifs = ["giphy1.gif", "giphy2.gif", "giphy3.gif", "giphy4.gif"];
    $randomGif = $gifs[array_rand($gifs)];

    return '/assets/' . $randomGif;
}