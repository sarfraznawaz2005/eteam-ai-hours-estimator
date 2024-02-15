<?php

class DB
{
    private $conn;
    private static $instance = null;

    // Constructor is private to prevent external instantiation
    private function __construct()
    {
        $this->connect();
    }

    // Clone method is private to prevent cloning of the instance
    private function __clone()
    {}

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        } else {
            if (!self::$instance->conn || !self::$instance->ping()) {
                self::$instance->connect();
            }
        }

        return self::$instance;
    }

    private function connect()
    {
        try {

            $dsn = 'mysql:host=' . CONFIG['db_host'] . ';dbname=' . CONFIG['db_name'] . ';charset=utf8';

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => true, // Enable persistent connection
            ];

            $this->conn = new PDO($dsn, CONFIG['db_user'], CONFIG['db_pass'], $options);
        } catch (PDOException $e) {
            logMessage('DB Connection Error: ' . $e->getMessage(), 'danger');

            $this->conn = null;
        }
    }

    // Method to check if the connection is alive
    private function ping()
    {
        try {
            $this->conn->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Execute a query (Create, Update, Delete)
    public function executeQuery($sql, $params = [])
    {
        try {
            // Constructing a representation of the final query for debugging purposes
            $debugQuery = $sql;

            foreach ($params as $key => $value) {
                // If the placeholder is named, replace it directly
                if (is_string($key)) {
                    $debugQuery = str_replace($key, "'" . $value . "'", $debugQuery);
                } else {
                    // For positional placeholders, this simple replacement may not work correctly for all queries
                    $debugQuery = preg_replace('/\?/', "'" . $value . "'", $debugQuery, 1);
                }
            }

            //logMessage($debugQuery);

            $stmt = $this->conn->prepare($sql);

            $success = $stmt->execute($params);

            return $success ? $stmt : false; // Returns false on failure
        } catch (PDOException $e) {
            logMessage('DB Query Error: ' . $e->getMessage(), 'danger');

            return false; // Indicate failure
        }
    }

    // Get data (Read)

    // $sql = "SELECT * FROM users";
    // $users = $db->get($sql);
    // foreach ($users as $user) {
    //     echo "Name: " . $user['name'] . ", Email: " . $user['email'] . "<br>";
    // }

    public function get($sql, $params = [])
    {
        $stmt = $this->executeQuery($sql, $params);

        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return false; // Indicate failure
        }
    }

    // Insert data into a table

    // $userData = [
    //     'name' => 'John Doe',
    //     'email' => 'john.doe@example.com'
    // ];
    // $db->insert('users', $userData);

    public function insert($table, $data)
    {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);

        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        $stmt = $this->executeQuery($sql, $data);

        return $stmt ? $stmt->rowCount() : false; // Number of affected rows or false
    }

    // Update data in a table

    // $updateData = [
    //     'email' => 'new.email@example.com'
    // ];
    // $userId = 1; // Assuming the ID of the user you want to update is 1
    // $db->update('users', $updateData, [id => $userId]);

    public function update($table, $data, $condition)
    {
        $updates = [];

        foreach ($data as $key => $value) {
            $updates[] = "$key = :$key";
        }

        $updatesString = implode(', ', $updates);

        $conditionString = [];
        $conditionParams = [];

        foreach ($condition as $key => $value) {
            $conditionString[] = "$key = :cond_$key"; // Prefixing condition keys to avoid name collision
            $conditionParams["cond_$key"] = $value; // Prefix the condition keys for binding
        }

        $conditionString = implode(' AND ', $conditionString);

        $sql = "UPDATE $table SET $updatesString WHERE $conditionString";
        $params = array_merge($data, $conditionParams); // Merge data and condition parameters

        $stmt = $this->executeQuery($sql, $params);

        return $stmt ? $stmt->rowCount() : false; // Number of affected rows or false
    }

    // Delete data from a table
    // $db->delete('users', "[id = 1]");

    public function delete($table, $condition)
    {
        $conditionString = [];
        $conditionParams = [];

        foreach ($condition as $key => $value) {
            $conditionString[] = "$key = :$key"; // Prepare condition for binding
            $conditionParams[$key] = $value; // Add the condition value to the parameters array
        }

        $conditionString = implode(' AND ', $conditionString);
        $sql = "DELETE FROM $table WHERE $conditionString";

        $stmt = $this->executeQuery($sql, $conditionParams); // Use $conditionParams to bind parameters safely

        return $stmt ? $stmt->rowCount() : false; // Return the number of affected rows or false
    }

}
