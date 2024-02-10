<?php

require_once '../../setup.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //sleep(5); exit(json_encode(['result' => "All Good!"]));

    $databaseTypeSelect = $_POST['databaseTypeSelect'] ?? '';
    $descriptionTextarea = $_POST['descriptionTextarea'] ?? '';
    $queryInput = $_POST['queryInput'] ?? '';

    // Validate the input
    if (empty($databaseTypeSelect) || empty($descriptionTextarea) || empty($queryInput)) {
        echo json_encode(['error' => 'All fields are required.']);
        http_response_code(400); // Bad Request
        exit;
    }

    GoogleAI::SetConfig(getConfig());

    $prompt = <<<PROMPT
\n\n

Act as $databaseTypeSelect server. Use below tables and fill them with 100 rows but don't show rows inserted output in your response.
\n\n
Tables:
$descriptionTextarea
\n\n

Use following format:

- Query Output:
$queryInput [use markdown table format when possible]

- SQL Query Ran [if possible]:

PROMPT;

    try {

        GoogleAI::setPrompt($prompt . file_get_contents('prompt.txt'));

        $response = GoogleAI::GenerateContentWithRetry();

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
