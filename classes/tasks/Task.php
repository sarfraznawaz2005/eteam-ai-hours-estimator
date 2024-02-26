<?php

abstract class Task
{
    abstract public static function execute();

    // for tasks running longer than a minute (cron time), we can avoid
    // double entries using lock file mechanism.
    public static function isAlreadyRunning(): bool
    {
        $lockFile = basePath() . '/' . get_called_class() . '.lock';

        // delete the $lockFile if it's older than x minutes
        // cause no task should take this much time
        $minutes = 5;
        if (file_exists($lockFile)) {
            $fileModificationTime = filemtime($lockFile);
            $timeNow = time();

            if (($timeNow - $fileModificationTime) >= ($minutes * 60)) {
                @unlink($lockFile);
            }
        }

        if (file_exists($lockFile)) {
            return true;
        }

        file_put_contents($lockFile, "Running");

        register_shutdown_function(function () use ($lockFile) {
            @unlink($lockFile);
        });

        return false;
    }

    public static function getInfo(string $activityId)
    {
        $DB = new DB();

        $result = $DB->get("SELECT * FROM activities WHERE activity_id = :activity_id", [':activity_id' => $activityId]);

        if (!$result) {
            return [];
        }

        return $result[0] ?? [];
    }

    public static function isDone(string $activityId, string $description)
    {
        $DB = new DB();

        ### description is added in check because possibly id can be same for
        ### posts and comments on basecamp for example.

        return $DB->get(
            "SELECT id FROM activities WHERE activity_id = :activity_id AND description = :description",
            [':activity_id' => $activityId, ':description' => $description]
        );
    }

    public static function isDoneForToday(string $activityId, string $description)
    {
        $DB = new DB();

        //////////////////////////////////
        // delete older records
        $sql = "DELETE FROM activities WHERE activity_id = '$activityId' AND description = '$description' AND DATE(created_at) < DATE(NOW() - INTERVAL 10 DAY)";
        $DB->executeQuery($sql);
        //////////////////////////////////

        return $DB->get(
            "SELECT id FROM activities WHERE DATE(created_at) = CURDATE() AND activity_id = :activity_id AND description = :description",
            [':activity_id' => $activityId, ':description' => $description]
        );
    }

    public static function markDone(string $activityId, string $description)
    {
        $DB = new DB();

        return $DB->insert('activities', [
            'activity_id' => $activityId,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
