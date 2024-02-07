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
We want to develop "$projectTypeSelect website". List all things we must consider to develop it.
\n
PROMPT;

    $prompt = <<<PROMPT
\n\n
Project Description:
$projectDescription

PROMPT;

    // send the request
    try {

        GoogleAI::SetSystemPrompt(file_get_contents('prompt_general.txt'));

        $response1 = GoogleAI::GenerateContentWithRetry($promptGeneral);

        sleep(3); // sleep a little before sending another request

        GoogleAI::SetSystemPrompt(file_get_contents('prompt.txt'));

        $response2 = GoogleAI::GenerateContentWithRetry($prompt);

        $response = Parsedown::instance()->text("##### General Considerations") . '<hr>' . $response1 . Parsedown::instance()->text("##### Technical Considerations") . '<hr>' . $response2;

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
