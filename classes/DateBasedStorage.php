<?php

class DateBasedStorage
{
    private static $prefix;
    private static $filePath;
    private static $directory = 'tmp_data';

    // Sets the file prefix and automatically determines the file path
    public static function initialize($prefix)
    {
        self::$prefix = $prefix;

        self::createDirectoryIfNeeded();
        self::updateFilePath();
        self::deleteOldFiles();
    }

    // Checks if the directory exists and creates it if not
    private static function createDirectoryIfNeeded()
    {
        if (!is_dir(self::$directory)) {
            mkdir(self::$directory, 0777, true);
        }
    }

    // Updates the file path based on the current date
    private static function updateFilePath()
    {
        $date = date('d-m-Y');

        self::$filePath = sprintf('%s/%s-%s.dat', self::$directory, self::$prefix, $date);
    }

    // Deletes files with the same prefix that are older than today
    private static function deleteOldFiles()
    {
        $files = glob(self::$directory . '/' . self::$prefix . '-*.dat'); // Get all files with the prefix
        $today = new DateTime('today');

        foreach ($files as $file) {
            $filename = basename($file, ".dat");
            $datePart = substr($filename, strlen(self::$prefix) + 1);
            $fileCreationDate = DateTime::createFromFormat('d-m-Y', $datePart);

            if ($fileCreationDate < $today) {
                unlink($file);
            }
        }
    }

    // Saves data to file
    public static function save($data)
    {
        if (!isset(self::$filePath)) {
            throw new Exception("File prefix is not set.");
        }

        $serializedData = serialize($data);

        file_put_contents(self::$filePath, $serializedData);
    }

    // Reads data from file
    public static function read()
    {
        if (isset(self::$filePath) && file_exists(self::$filePath)) {

            $serializedData = file_get_contents(self::$filePath);

            return unserialize($serializedData);
        }

        return null; // Return null if the file does not exist
    }

    // Sets new data (overwrites existing)
    public static function set($newData)
    {
        self::save($newData);
    }

    // Utility function to manually delete the current day's file (optional)
    public static function delete()
    {
        if (isset(self::$filePath) && file_exists(self::$filePath)) {
            @unlink(self::$filePath);
        }
    }
}
