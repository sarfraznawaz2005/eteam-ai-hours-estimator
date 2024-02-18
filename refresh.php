<?php
/////////////////////////////////////
// delete tasks lock files
/////////////////////////////////////

// Array of prefixes for lock files to skip (e.g., 'composer', 'package', etc.)
$skipLockFiles = ['composer', 'package'];

$files = glob('*.lock');

foreach ($files as $file) {
    $skipFile = false;

    // Check if file name starts with one of the prefixes in $skipLockFiles
    foreach ($skipLockFiles as $prefix) {
        if (strpos($file, $prefix) === 0) {
            $skipFile = true;
            break;
        }
    }

    // If file is to be skipped, continue to the next file
    if ($skipFile) {
        echo "Skipping lock file: $file<br>";
        continue;
    }

    // Try to open the file
    $handle = @fopen($file, "r+");

    // If the file could be opened
    if ($handle) {
        // Try to lock the file for exclusive access
        if (flock($handle, LOCK_EX | LOCK_NB)) {
            // If the lock was acquired, unlock and close handle, then unlink (delete) the file
            flock($handle, LOCK_UN);
            fclose($handle);

            //if (@unlink($file)) {
                echo "Deleted lock file: $file<br>";
            //}
        } else {
            // Couldn't lock the file, it might be in use, so close the handle and skip deletion
            fclose($handle);
        }
    }
}
