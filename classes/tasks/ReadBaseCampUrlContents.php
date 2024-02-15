<?php

class ReadBaseCampUrlContents extends Task
{
    public static function execute()
    {
        //logMessage('Running: ' . __CLASS__);

        if (static::isAlreadyRunning()) {
            exit(1);
        }

        try {

            $output = BasecampClassicAPI::getUrlContents('https://eteamid.basecamphq.com/projects/3335441-eteam-knowledge-sharing/posts/115573370/comments');

            libxml_use_internal_errors(true); // Disable libxml errors
            $dom = new DOMDocument();
            @$dom->loadHTML($output);
            libxml_clear_errors(); // Clear any errors that were stored

            $dom->preserveWhiteSpace = false;

            $originalPostElement = $dom->getElementById('OriginalPost');

            if ($originalPostElement) {
                $originalPostContent = $dom->saveHTML($originalPostElement);
                echo strip_tags($originalPostContent);
            } else {
                echo "Error or element with ID 'OriginalPost' not found.";
            }

        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

        exit(1);
    }
}
