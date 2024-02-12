<?php

class IniReader
{
    private static $filePath;
    private static $data = [];
    private static $section = 'settings'; // Default section for all operations

    public static function initialize()
    {
        $rootFolder = __DIR__;

        $mostRecentFilePath = self::cleanupOldFiles();

        $today = date('d-m-Y');
        self::$filePath = dirname($rootFolder) . DIRECTORY_SEPARATOR . "todo-$today.ini";

        if (!file_exists(self::$filePath)) {
            if ($mostRecentFilePath && file_exists($mostRecentFilePath) && $mostRecentFilePath !== self::$filePath) {
                copy($mostRecentFilePath, self::$filePath);
                
                self::read();
            } else {
                self::$data[self::$section] = [];
                self::write(true);
            }
        } else {
            self::read();
        }
    }

    private static function read()
    {
        if (file_exists(self::$filePath)) {
            self::$data = parse_ini_file(self::$filePath, true);

            if (!self::$data) {
                self::$data = []; // Handle parse errors by initializing to an empty array
            }
        } else {
            self::$data = [];
        }
    }

    public static function get($key)
    {
        if (isset(self::$data[self::$section][$key])) {
            return self::$data[self::$section][$key];
        }

        return null;
    }

    public static function set($key, $value)
    {
        // Sanitize the key to make it valid for INI files
        $sanitizedKey = self::sanitizeKey($key);

        self::$data[self::$section][$sanitizedKey] = $value;

        self::write();
    }

    private static function write($initialize = false)
    {
        if (!$initialize) {
            // Acquire an exclusive lock to prevent concurrent writes
            $fp = fopen(self::$filePath, 'c');

            if (flock($fp, LOCK_EX)) {
                $content = self::generateContent();
                ftruncate($fp, 0); // Truncate file to rewrite it
                fwrite($fp, $content);
                fflush($fp); // Flush output before releasing the lock
                flock($fp, LOCK_UN); // Release the lock
            } else {
                // Handle error: unable to acquire lock
                throw new Exception("Unable to acquire lock on file " . self::$filePath);
            }

            fclose($fp);
        } else {
            // For initialization, just write without locking
            file_put_contents(self::$filePath, self::generateContent());
        }
    }

    private static function generateContent()
    {
        $content = "[" . self::$section . "]\n";

        foreach (self::$data[self::$section] as $key => $value) {
            $content .= "$key = \"$value\"\n";
        }

        return $content;
    }

    public static function isLocked()
    {
        $locked = false;

        // Open the file in read mode ('r') to check the lock status
        $fp = @fopen(self::$filePath, 'r');
        if ($fp) {
            // Try to acquire an exclusive lock without blocking (LOCK_NB)
            if (!flock($fp, LOCK_EX | LOCK_NB)) {
                // If we cannot acquire the lock, it means the file is locked by another process
                $locked = true;
            }

            fclose($fp);
        }

        return $locked;
    }

    private static function sanitizeKey($key)
    {
        // Replace spaces with underscores, remove special characters, and ensure it doesn't start with numbers
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
        $sanitizedKey = preg_replace('/^\d+/', '', $sanitizedKey);

        if (empty($sanitizedKey)) {
            $sanitizedKey = 'key_' . time();
        }

        return $sanitizedKey;
    }

    private static function cleanupOldFiles()
    {
        $files = glob(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'todo-*.ini');

        if (count($files) <= 1) {
            return current($files); // Return the only file or false if none
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $mostRecentFile = array_shift($files); // Keep the most recent file

        foreach ($files as $file) {
            @unlink($file);
        }

        return $mostRecentFile;
    }

}
