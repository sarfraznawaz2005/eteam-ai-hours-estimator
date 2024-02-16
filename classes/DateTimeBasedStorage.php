<?php

class DateTimeBasedStorage
{
    private string $prefix;
    private string $filePath;
    private string $directory = 'tmp_data';
    private string $mode; // 'date' or 'time'
    private int $timeInterval; // Time interval in minutes for time mode

    /**
     * @throws Exception
     */
    public function __construct($prefix, $mode = 'date', $timeInterval = 5)
    {
        $this->prefix = $prefix;
        $this->mode = $mode;
        $this->timeInterval = $timeInterval;
        $this->validateModeAndInterval();

        $this->createDirectoryIfNeeded();
        $this->updateFilePath();
        $this->deleteOldFiles();
    }

    private function validateModeAndInterval()
    {
        if (!in_array($this->mode, ['date', 'time'])) {
            throw new Exception("Invalid mode specified. Use 'date' or 'time'.");
        }
        if ($this->mode === 'time' && (!$this->timeInterval)) {
            throw new Exception("Time mode requires a valid time interval in minutes.");
        }
    }

    private function createDirectoryIfNeeded()
    {
        $this->directory = basePath() . '/' . $this->directory;

        if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true)) {
            throw new Exception("Failed to create directory: " . $this->directory);
        }
    }

    // Updates the file path based on the current date
    private function updateFilePath()
    {
        $now = new DateTime();
        $dateTimeFormat = $this->mode === 'time' ? 'd-m-Y-H-i' : 'd-m-Y';
        $currentDateTime = $now->format($dateTimeFormat);
        $directoryFiles = glob($this->directory . '/' . $this->prefix . '-*.dat');

        // Initialize with a path for potentially creating a new file
        $potentialFilePath = sprintf('%s/%s-%s.dat', $this->directory, $this->prefix, $currentDateTime);

        if ($this->mode === 'date') {
            // In date mode, we only care if there's a file for today, regardless of the time
            foreach ($directoryFiles as $file) {
                if (str_contains($file, $currentDateTime)) {
                    // A file for today exists, so use it
                    $this->filePath = $file;
                    return;
                }
            }
        } else if ($this->mode === 'time') {
            // In time mode, check if a recent file exists within the time interval
            foreach ($directoryFiles as $file) {
                $filename = basename($file, ".dat");
                $datetimePart = substr($filename, strlen($this->prefix) + 1);
                $fileDateTime = DateTime::createFromFormat('d-m-Y-H-i', $datetimePart);

                if ($fileDateTime !== false) {
                    $interval = $now->diff($fileDateTime);
                    $minutesPassed = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

                    if ($minutesPassed < $this->timeInterval) {
                        // A recent file exists within the time interval, so use it
                        $this->filePath = $file;
                        return;
                    }
                }
            }
        }

        // No suitable file found; proceed with the path for a new file
        $this->filePath = $potentialFilePath;
    }

    // Deletes files with the same prefix that are older than today
    private function deleteOldFiles()
    {
        $files = glob($this->directory . '/' . $this->prefix . '-*.dat');

        foreach ($files as $file) {
            if ($this->isFileExpired($file)) {
                @unlink($file);
            }
        }
    }

    private function isFileExpired($file)
    {
        $filename = basename($file, ".dat");
        $datetimePart = substr($filename, strlen($this->prefix) + 1);
        $fileDateTime = DateTime::createFromFormat('d-m-Y-H-i', $datetimePart);

        if ($fileDateTime === false) {
            // Failed to create DateTime object; handle appropriately
            return false;
        }

        $now = new DateTime();
        $today = new DateTime('today');

        if ($this->mode === 'date') {
            // In date mode, consider the file expired if it's not from today
            return $fileDateTime->format('Y-m-d') < $today->format('Y-m-d');
        } else if ($this->mode === 'time') {
            // In time mode, check if the file is from today and if the time interval has passed
            $isFromToday = $fileDateTime->format('Y-m-d') === $today->format('Y-m-d');

            if (!$isFromToday) {
                return true; // Files not from today are expired
            }

            $interval = $now->diff($fileDateTime);
            $minutesPassed = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
            // The file is expired if the time interval has passed
            return $minutesPassed >= $this->timeInterval;
        }

        // Default case (should not be reached if modes are correctly validated)
        return false;
    }

    // Saves data to file

    /**
     * @throws Exception
     */
    public function save($data)
    {
        if (!$data) {
            return;
        }

        if (!isset($this->filePath)) {
            throw new Exception("File prefix is not set.");
        }

        // Convert data to JSON before saving, using JSON_PRETTY_PRINT for readability
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);

        if ($jsonData === false) {
            throw new Exception("Failed to convert data to JSON.");
        }

        $file = fopen($this->filePath, 'c');

        if (!$file || !flock($file, LOCK_EX)) {
            throw new Exception("Failed to lock file for writing: " . $this->filePath);
        }

        ftruncate($file, 0); // Clear the file content
        fwrite($file, $jsonData);
        fflush($file); // Flush output before releasing the lock
        flock($file, LOCK_UN); // Release the lock
        fclose($file);
    }

    // Modify the read method to potentially convert arrays back to SimpleXMLElement
    public function read()
    {
        if (isset($this->filePath) && file_exists($this->filePath)) {
            $file = fopen($this->filePath, 'r');

            if (!$file || !flock($file, LOCK_SH)) {
                throw new Exception("Failed to lock file for reading: " . $this->filePath);
            }

            $jsonData = stream_get_contents($file);

            flock($file, LOCK_UN);
            fclose($file);

            $data = json_decode($jsonData, true); // Decode as associative array
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Failed to decode JSON data.");
            }

            return $data;
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
