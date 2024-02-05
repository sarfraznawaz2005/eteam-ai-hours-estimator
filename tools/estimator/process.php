<?php

require_once '../../utility/ai.php';

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
    $response = generateContentWithRetry($prompt);

    echo json_encode(['result' => $response]);

    http_response_code(200); // OK
} else {
    echo json_encode(['error' => 'Invalid request method.']);
    http_response_code(405); // Method Not Allowed
}