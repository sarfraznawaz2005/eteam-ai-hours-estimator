<?php

abstract class Task
{
    abstract public static function execute();

    public static function getInfo(string $activityId)
    {
        $DB = DB::getInstance();

        $result = $DB->get("select done from activities where activity_id = :activity_id", [':activity_id' => $activityId]);

        if (!$result) {
            return false;
        }

        return $result[0] ?? [];
    }

    public static function isDone(string $activityId): bool
    {
        $DB = DB::getInstance();

        $result = $DB->get("select done from activities where activity_id = :activity_id", [':activity_id' => $activityId]);

        if (!$result) {
            return false;
        }

        return $result[0]['done'] === 'Yes';
    }

    public static function isDoneForToday(string $activityId): bool
    {
        $DB = DB::getInstance();

        $result = $DB->get(
            "select done from activities where DATE(created_at) = DATE(NOW()) AND activity_id = :activity_id",
            [':activity_id' => $activityId]
        );

        if (!$result) {
            return false;
        }

        return $result[0]['done'] === 'Yes';
    }

    public static function markDone(string $activityId, $description)
    {
        $DB = DB::getInstance();

        $exists = static::getInfo($activityId);

        if ($exists) {
            $result = $DB->update('activities', ['done' => 'Yes'], ['activity_id' => $activityId]);
        } else {
            $result = $DB->insert('activities', [
                'activity_id' => $activityId,
                'description' => $description,
                'done' => 'Yes',
                'created_at' => now(),
            ]);
        }

        if ($result) {
            logMessage($description . ' : Marked Done', 'success');
        } else {
            logMessage($description . ' : Unable to mark done', 'danger');
        }
    }
}
