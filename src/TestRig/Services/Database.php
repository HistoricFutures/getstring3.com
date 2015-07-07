<?php

/**
 * @file
 * Database library calls.
 */

namespace TestRig\Services;

/**
 * @class
 * Database methods.
 */
class Database
{
    /**
     * Create a valid SQLite database, and optionally populate it with a schema.
     *
     * @param string $path
     *   Path to the file.
     * @throws Exception
     *   If file already exists.
     */
    public static function create($path)
    {
        if (file_exists($path))
        {
            throw new \Exception("Tried to create SQLite db but '$path' exists.");
        }
        // Open with default flags: will create if it doesn't exist.
        $conn = self::getConn($path, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

        // Create schema.
        $conn->exec(file_get_contents(__DIR__ . "/../resources/schema.sql"));
    }

    /**
     * Get count data for a table.
     */
    public static function getTableCount($path, $table)
    {
        // Get connection and escape arguments.
        $conn = self::getConn($path);
        $table = \SQLite3::escapeString($table);

        // Make query and return first value we find.
        $results = $conn->query("SELECT COUNT(*) AS result FROM $table");
        while ($row = $results->fetchArray())
        {
            return $row['result'];
        }
    }

    /**
     * Get (new) connection to SQLite databse.
     *
     * @param string $path
     *   Path to SQLite file.
     * @return \SQLite3
     *   SQLite connection object.
     */
    public static function getConn($path, $flags = SQLITE3_OPEN_READWRITE)
    {
        return new \SQLite3($path, $flags);
    }

    /**
     * Write a row to a database table.
     *
     * @param string $path
     *   Path to SQLite file.
     * @param string $table
     *   Table name.
     * @param array &$record
     *   Key/value array to write; we insert the ID into this array.
     */
    public static function writeRecord($path, $table, &$record)
    {
        $conn = self::getConn($path);
        $table = \SQLite3::EscapeString($table);

        // Ensure columns and arguments match up.
        $columns = array();
        $arguments = array();
        foreach ($record as $column => $argument)
        {
            $columns[] = $column;
            $arguments[":$column"] = $argument;
        }

        // Create SQL based on $columns, sneaking a ":" in there for VALUES.
        $sql = "INSERT INTO $table (" . implode(", ", $columns) .
            ") VALUES(:" . implode(", :", $columns) . ");";
        $statement = $conn->prepare($sql);

        // Bind all values and execute.
        foreach ($arguments as $bindKey => $bindValue)
        {
            $statement->bindValue($bindKey, $bindValue);
        }
        $statement->execute();

        // Using same connection, inject the autoincrement ID into $record.
        $statement = $conn->prepare("SELECT last_insert_rowid() AS id;");
        $results = $statement->execute();
        while ($row = $results->fetchArray())
        {
            $record["id"] = $row['id'];
            return $row['id'];
        }
    }
}
