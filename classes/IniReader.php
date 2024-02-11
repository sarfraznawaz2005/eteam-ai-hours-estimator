<?php

class IniReader {
    private $filePath;
    private $data;

    public function __construct() {
        $this->cleanupOldFiles();
        $today = date('Y-m-d');
        $this->filePath = "todo-$today.ini";
        $this->read();
    }

    private function read() {
        if (file_exists($this->filePath)) {
            $this->data = parse_ini_file($this->filePath, true);
        } else {
            $this->data = [];
        }
    }

    public function get($section, $key) {
        if (isset($this->data[$section][$key])) {
            return $this->data[$section][$key];
        }
        return null; // Key not found
    }

    public function set($section, $key, $value) {
        $this->data[$section][$key] = $value;
        $this->write();
    }

    private function write() {
        $content = '';
        foreach ($this->data as $section => $values) {
            $content .= "[$section]\n";
            foreach ($values as $key => $value) {
                $content .= "$key = \"$value\"\n";
            }
        }
        file_put_contents($this->filePath, $content);
    }

    private function cleanupOldFiles() {
        $files = glob('todo-*.ini'); // Get all todo files
        $today = date('Y-m-d');
        foreach ($files as $file) {
            if (preg_match('/todo-(\d{4}-\d{2}-\d{2})\.ini$/', $file, $matches)) {
                if ($matches[1] < $today) {
                    unlink($file); // Delete file older than today
                }
            }
        }
    }
}