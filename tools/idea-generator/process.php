<?php

require_once '../../setup.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ideaInput = $_POST['ideaInput'] ?? '';
    $niche = $_POST['niche'] ?? '';

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

        $aiModel = AIFactory::getAIModel();
        //logMessage('Model: ' . $aiModel::class, 'success');

        if (!empty($niche)) {
            $aiModel::setPrompt(file_get_contents('prompt_niche.txt') . $prompt);
        } else {
            $aiModel::setPrompt(file_get_contents('prompt.txt') . $prompt);
        }

        $response = $aiModel::GenerateContentWithRetry();

        echo json_encode(['result' => $response]);
        //http_response_code(200); // OK
    } catch (Exception $e) {

        if (str_contains(strtolower($e->getMessage()), 'candidates')) {
            echo json_encode(['error' => 'There was some error, please try again later.']);
        } else {
            echo json_encode(['error' => $e->getMessage() . ' on line ' . $e->getLine()]);
        }
    }

} else {
    echo json_encode(['error' => 'Invalid request method.']);
    http_response_code(405); // Method Not Allowed
}
