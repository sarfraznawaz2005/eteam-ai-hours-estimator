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

    GoogleAI::SetConfig(getConfig());

    $promptGeneral = <<<PROMPT
\n
We want to develop "$projectTypeSelect website". List all things we must consider to develop it. You must be specific to our website not too general.
\n
PROMPT;

    $prompt = <<<PROMPT
\n\n
Project Description:
$projectDescription

Project Type: $projectTypeSelect

PROMPT;

    try {

        $prompts = [
            [
                'system_prompt' => file_get_contents('prompt_general.txt'),
                'user_prompt' => $promptGeneral,
            ],
            [
                'system_prompt' => file_get_contents('prompt.txt'),
                'user_prompt' => $prompt,
            ],
        ];

        $response = GoogleAI::generateMultipleContents($prompts, 'generateContentWithRetry');

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
