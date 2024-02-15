<?php

class DateBasedStorage
{
    private $prefix;
    private $filePath;
    private $directory = 'tmp_data';

    // Sets the file prefix and automatically determines the file path
    public function __construct($prefix)
    {
        $this->prefix = $prefix;

        $this->createDirectoryIfNeeded();
        $this->updateFilePath();
        $this->deleteOldFiles();
    }

    // Checks if the directory exists and creates it if not
    private function createDirectoryIfNeeded()
    {
        $this->directory = basePath() . '/' . $this->directory;
        
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    // Updates the file path based on the current date
    private function updateFilePath()
    {
        $date = date('d-m-Y');

        $this->filePath = sprintf('%s/%s-%s.dat', $this->directory, $this->prefix, $date);
    }

    // Deletes files with the same prefix that are older than today
    private function deleteOldFiles()
    {
        $files = glob($this->directory . '/' . $this->prefix . '-*.dat'); // Get all files with the prefix
        $today = new DateTime('today');

        foreach ($files as $file) {
            $filename = basename($file, ".dat");
            $datePart = substr($filename, strlen($this->prefix) + 1);
            $fileCreationDate = DateTime::createFromFormat('d-m-Y', $datePart);

            if ($fileCreationDate < $today) {
                unlink($file);
            }
        }
    }

    // Saves data to file
    public function save($data)
    {
        if (!isset($this->filePath)) {
            throw new Exception("File prefix is not set.");
        }

        if ($data) {
            $serializedData = serialize($data);

            file_put_contents($this->filePath, $serializedData);
        }
    }

    // Reads data from file
    public function read()
    {
        if (isset($this->filePath) && file_exists($this->filePath)) {

            $serializedData = file_get_contents($this->filePath);

            return unserialize($serializedData);
        }

        return null; // Return null if the file does not exist
    }

    // Sets new data (overwrites existing)
    public function set($newData)
    {
        $this->save($newData);
    }

    // Utility function to manually delete the current day's file (optional)
    public function delete()
    {
        if (isset($this->filePath) && file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    }
}
