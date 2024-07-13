<?php

require_once '../../setup.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //sleep(5); exit(json_encode(['result' => "All Good!"]));

    $projectDescription = $_POST['descriptionTextarea'] ?? '';
    $projectTypeSelect = $_POST['projectTypeSelect'] ?? '';

    // Validate the input
    if (empty($projectDescription)) {
        echo json_encode(['error' => 'Project description is required.']);
        http_response_code(400); // Bad Request
        exit;
    }

    $promptGeneral = <<<PROMPT
\n
You want to develop "$projectTypeSelect platform". You will list all things that must considered to develop it. You must be specific to platform type instead of being too general.
\n
PROMPT;

    $prompt = <<<PROMPT
\n\n

Here are further project details:
1. Platform Type: $projectTypeSelect
2. Over 100000 users will be using it with 10000 daily active users.

\n\n
Project Description:
$projectDescription

PROMPT;

    try {

        $response = "<h4>TECHNICAL GUIDELINES:</h4>";

        $aiModel = AIFactory::getAIModel();

        $aiModel::setPrompt(file_get_contents('prompt.txt') . $prompt);

        $response .= $aiModel::GenerateContentWithRetry();

        sleep(3);

        $response .= "<h4>GENERAL GUIDELINES:</h4>";

        $aiModel::setPrompt(file_get_contents('prompt_general.txt') . $promptGeneral);

        $response .= $aiModel::GenerateContentWithRetry();

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
