<?php

require_once '../../setup.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ideaInput = $_POST['ideaInput'] ?? '';
    $niche = $_POST['niche'] ?? '';

    GoogleAI::SetConfig(getConfig());

    if (empty($ideaInput)) {
        $prompt = <<<PROMPT
\n
Please generate a random software product idea based on given instructions.

PROMPT;
    } else {
        $prompt = <<<PROMPT
\n
My first request is "$ideaInput"

PROMPT;
    }

    if (!empty($niche)) {
        $prompt = <<<PROMPT
        \n
        Keyword(s): "$niche"

PROMPT;
    }

    // send the request
    try {

        if (!empty($niche)) {
            GoogleAI::SetSystemPrompt(file_get_contents('prompt_niche.txt'));
        } else {
            GoogleAI::SetSystemPrompt(file_get_contents('prompt.txt'));
        }

        $response = GoogleAI::GenerateContentWithRetry($prompt);

        echo json_encode(['result' => $response]);
        http_response_code(200); // OK
    } catch (Exception $e) {

        if (str_contains($e->getMessage(), 'candidates')) {
            echo json_encode(['error' => 'There was some error, please try again later.']);
        } else {
            echo json_encode(['error' => $e->getMessage() . ' on line ' . $e->getLine()]);
        }
    }

} else {
    echo json_encode(['error' => 'Invalid request method.']);
    http_response_code(405); // Method Not Allowed
}
