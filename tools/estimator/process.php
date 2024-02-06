<?php

require_once '../../setup.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //sleep(5); exit(json_encode(['result' => "All Good!"]));

    $projectDescription = $_POST['descriptionTextarea'] ?? '';
    $projectFeatures = $_POST['featuresTextarea'] ?? '';

    // Validate the input
    if (empty($projectDescription) || empty($projectFeatures)) {
        echo json_encode(['error' => 'Project description and features are required.']);
        http_response_code(400); // Bad Request
        exit;
    }

    $prompt = <<<PROMPT

Project Description:
$projectDescription

Features:
$projectFeatures

PROMPT;

    // send the request
    try {

        GoogleAI::SetSystemPrompt(file_get_contents('prompt.txt'));

        $response = GoogleAI::GenerateContentWithRetry($prompt, true);

        // calculate total estimate manually since AI is weak in maths
        $pattern = '/\d+(?= hours)/';
        preg_match_all($pattern, $response, $matches);

        $total = array_sum($matches[0]);

        $response = $response . "<hr><strong>Total Rough Estimate: $total</strong>";

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
