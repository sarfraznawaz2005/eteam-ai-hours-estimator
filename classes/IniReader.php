<?php

class IniReader
{
    private static $filePath;
    private static $data = [];
    private static $section = 'settings'; // Default section for all operations

    public static function initialize()
    {
        $rootFolder = __DIR__;

        self::cleanupOldFiles();

        $today = date('d-m-Y');
        self::$filePath = dirname($rootFolder) . DIRECTORY_SEPARATOR . "todo-$today.ini";

        if (!file_exists(self::$filePath)) {
            self::$data[self::$section] = [];
            self::write();
        } else {
            self::read();
        }
    }

    // Loads the INI file into an array.
    private static function read()
    {
        if (file_exists(self::$filePath)) {
            self::$data = parse_ini_file(self::$filePath, true);
        } else {
            self::$data = [];
        }
    }

    // Retrieves a value from the settings section.
    public static function get($key)
    {
        if (isset(self::$data[self::$section][$key])) {
            return self::$data[self::$section][$key];
        }

        return null; // Key not found
    }

    // Sets a value in the settings section and writes the changes back to the file.
    public static function set($key, $value)
    {
        self::$data[self::$section][$key] = $value;

        self::write();
    }

    // Saves the current state of the data array back to the INI file.
    private static function write()
    {
        $content = "[" . self::$section . "]\n";

        foreach (self::$data[self::$section] as $key => $value) {
            $content .= "$key = \"$value\"\n";
        }

        file_put_contents(self::$filePath, $content);
    }

    private static function cleanupOldFiles()
    {
        $files = glob('todo-*.ini');
        $today = new DateTime();

        foreach ($files as $file) {
            if (preg_match('/todo-(\d{2})-(\d{2})-(\d{4})\.ini$/', $file, $matches)) {
                $fileDate = DateTime::createFromFormat('d-m-Y', $matches[1] . '-' . $matches[2] . '-' . $matches[3]);

                if ($fileDate < $today) {
                    @unlink($file);
                }
            }
        }
    }

}
