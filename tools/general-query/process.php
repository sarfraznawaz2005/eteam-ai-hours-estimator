<?php

require_once '../../setup.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $description = $_POST['descriptionTextarea'] ?? '';

    if (empty($description)) {
        echo json_encode(['error' => 'Description is required.']);
        http_response_code(400);
        exit;
    }

    try {

        GoogleAI::setPrompt(file_get_contents('prompt.txt') . "\n\n $description");
        $response = GoogleAI::GenerateContentWithRetry();

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
