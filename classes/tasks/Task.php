<?php

abstract class Task
{
    abstract public static function execute();

    public static function getInfo(string $activityId)
    {
        $DB = DB::getInstance();

        $result = $DB->get("select * from activities where activity_id = :activity_id", [':activity_id' => $activityId]);

        if (!$result) {
            return [];
        }

        return $result[0] ?? [];
    }

    public static function isDone(string $activityId)
    {
        $DB = DB::getInstance();

        return $DB->get("select id from activities where activity_id = :activity_id", [':activity_id' => $activityId]);
    }

    public static function isDoneForToday(string $activityId)
    {
        $DB = DB::getInstance();

        //////////////////////////////////
        // delete older records
        $sql = "DELETE FROM activities WHERE activity_id = '$activityId' AND created_at < NOW() - INTERVAL 1 DAY";
        $DB->executeQuery($sql);
        //////////////////////////////////

        return $DB->get(
            "select id from activities where DATE(created_at) = DATE(NOW()) AND activity_id = :activity_id",
            [':activity_id' => $activityId]
        );
    }

    public static function markDone(string $activityId, $description)
    {
        $DB = DB::getInstance();

        return $DB->insert('activities', [
            'activity_id' => $activityId,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
