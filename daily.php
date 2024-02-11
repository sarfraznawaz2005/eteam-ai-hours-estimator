<?php

$iniReader = new IniReader();

function getProjectIdea()
{
    $isAlreadyDone = IniReader::get(__FUNCTION__);

    if (!$isAlreadyDone) {
        $subject = 'Daily Project Idea - ' . date('d-m-Y');

        GoogleAI::setPrompt(file_get_contents('./tools/idea-generator/prompt.txt') . "\n\nPlease generate a random software product idea based on given instructions.");

        $response = GoogleAI::GenerateContentWithRetry();

        if (!str_contains($response, 'No response')) {

            $emailSent = EmailSender::sendEmail('sarfraz@eteamid.com', 'Sarfraz', $subject, $response);

            if ($emailSent) {
                logMessage("Daily Project Idea: Email has been sent: {$subject}");

                IniReader::set(__FUNCTION__, 'true');
            } else {
                logMessage("Daily Project Idea: Error or no response: {$subject}", 'error');
            }
        } else {
            logMessage("Daily Project Idea: Error or no response: {$subject}", 'error');
        }
    }

}
