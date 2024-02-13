<?php

require_once '../../setup.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //sleep(5); exit(json_encode(['result' => "All Good!"]));

    $projectDescription = $_POST['descriptionTextarea'] ?? '';

    // Validate the input
    if (empty($projectDescription)) {
        echo json_encode(['error' => 'Project description is required.']);
        http_response_code(400); // Bad Request
        exit;
    }
    
    $prompt = <<<PROMPT
\n\n
Project Description:
$projectDescription

PROMPT;

    // send the request
    try {


        GoogleAI::setPrompt(file_get_contents('prompt.txt') . $prompt);
        $response = GoogleAI::GenerateContentWithRetry();

        // calculate total estimate manually since AI is weak in maths
        $pattern = '/\d+(?= hours)/';
        preg_match_all($pattern, $response, $matches);

        $total = array_sum($matches[0]);

        $response = $response . "<hr><strong>TOTAL ESTIMATED HOURS: $total</strong>";

        echo json_encode(['result' => $response]);
        http_response_code(200); // OK
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
