<?php

abstract class Task
{
    abstract public static function execute();

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
        $sql = "DELETE FROM activities WHERE activity_id = '$activityId' AND description = '$description' AND created_at < NOW() - INTERVAL 1 DAY";
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
