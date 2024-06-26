<?php

class IniReader
{
    private static string $filePath;
    private static array $data = [];
    private static string $section = 'settings'; // Default section for all operations

    /**
     * @throws Exception
     */
    public static function initialize(): void
    {
        $rootFolder = __DIR__;

        $mostRecentFilePath = self::cleanupOldFiles();

        $today = date('d-m-Y');
        self::$filePath = dirname($rootFolder) . DIRECTORY_SEPARATOR . "todo-$today.ini";

        if (!file_exists(self::$filePath)) {
            if ($mostRecentFilePath && file_exists($mostRecentFilePath) && $mostRecentFilePath !== self::$filePath) {

                // copy data from prev most recent file
                self::$data = parse_ini_file($mostRecentFilePath, true);

                copy($mostRecentFilePath, self::$filePath);

                // we want to delete these so they can run again
                static::delete(PostWorkPlan::class);
                static::delete(PostProjectIdea::class);

            } else {
                self::$data[self::$section] = [];
                self::write(true);
            }
        } else {
            self::read();
        }
    }

    private static function read(): void
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

    /**
     * @throws Exception
     */
    public static function set($key, $value): void
    {
        // Sanitize the key to make it valid for INI files
        $sanitizedKey = self::sanitizeKey($key);

        self::$data[self::$section][$sanitizedKey] = $value;

        self::write();
    }

    /**
     * @throws Exception
     */
    private static function write($initialize = false): void
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

    private static function generateContent(): string
    {
        $content = "[" . self::$section . "]\n";

        foreach (self::$data[self::$section] as $key => $value) {
            $content .= "$key = \"$value\"\n";
        }

        return $content;
    }

    /**
     * @throws Exception
     */
    public static function delete($key): void
    {
        $sanitizedKey = self::sanitizeKey($key);

        if (isset(self::$data[self::$section][$sanitizedKey])) {
            unset(self::$data[self::$section][$sanitizedKey]);

            self::write();
        }
    }

    public static function isLocked(): bool
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

    private static function sanitizeKey($key): array|string|null
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
