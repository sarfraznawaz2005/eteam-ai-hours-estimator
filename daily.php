<?php

$iniReader = new IniReader();

function getProjectIdea()
{
    $subject = 'Daily Project Idea';

    GoogleAI::setPrompt(file_get_contents('./tools/idea-generator/prompt.txt') . "\n\nPlease generate a random software product idea based on given instructions.");

    $response = GoogleAI::GenerateContentWithRetry();

    if (!str_contains($response, 'No response')) {

        $emailSent = EmailSender::sendEmail('sarfraz@eteamid.com', 'Sarfraz', $subject, $response);

        if ($emailSent) {
            logMessage("Daily Project Idea: Email has been sent: {$subject}");
        } else {
            logMessage("Daily Project Idea: Error or no response: {$subject}", 'error');
        }
    } else {
        logMessage("Daily Project Idea: Error or no response: {$subject}", 'error');
    }
}
