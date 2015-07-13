<?php

/**
 * @file
 * Database library calls.
 */

namespace TestRig\Services;

use TestRig\Exceptions\MissingDatasetFileException;

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

        // Return connection object.
        return $conn;
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
     * Get arbitrary aggregate data on a table.
     */
    public static function getTableAggregate($path, $table, $aggregate, $column)
    {
        // Get connection and escape arguments.
        $conn = self::getConn($path);
        $table = \SQLite3::escapeString($table);
        $aggregate = \SQLite3::escapeString($aggregate);
        $column = \SQLite3::escapeString($column);

        // Make query and return first value we find.
        $results = $conn->query("SELECT $aggregate($column) AS result FROM $table");
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
        // If the file exists, or we have a flag to create, open regardless.
        if (file_exists($path) || ($flags & SQLITE3_OPEN_CREATE))
        {
            return new \SQLite3($path, $flags);
        }
        // If no file, or we're not meant to create, raise exception.
        throw new MissingDatasetFileException($path);
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
            $column = \SQLite3::EscapeString($column);
            $columns[] = $column;
            $arguments[":$column"] = $argument;
        }

        // Create SQL based on $columns, sneaking a ":" in there for VALUES.
        // Cope with situation when there's no data and all values are default.
        if ($columns)
        {
            $sql = "INSERT INTO $table (" . implode(", ", $columns) .
                ") VALUES(:" . implode(", :", $columns) . ");";
        }
        else
        {
            $sql = "INSERT INTO $table DEFAULT VALUES;";
        }

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

    /**
     * Read a row from a database table.
     */
    public static function readRecord($path, $table, $id)
    {
        $conn = self::getConn($path);
        $table = \SQLite3::EscapeString($table);

        // Select all data from the row matching the ID.
        $sql = "SELECT * FROM $table WHERE id = :id";
        $statement = $conn->prepare($sql);
        $statement->bindValue(":id", $id);
        $results = $statement->execute();

        while ($row = $results->fetchArray(SQLITE3_ASSOC))
        {
            return $row;
        }
    }

    /**
     * Update a row in a database table.
     *
     * @param string $path
     *   Path to SQLite file.
     * @param string $table
     *   Table name.
     * @param integer $id
     *   ID of the row.
     * @param array $record
     *   Key/value array of *any* changes to write.
     */
    public static function updateRecord($path, $table, $id, $record)
    {
        $conn = self::getConn($path);
        $table = \SQLite3::EscapeString($table);

        // Never permit ID to be changed.
        unset($record['id']);

        // Create SQL based on $columns, sneaking a ":" in there for VALUES.
        $arguments = array();
        $sql = "UPDATE $table SET ";
        foreach ($record as $column => $argument)
        {
            $column = \SQLite3::EscapeString($column);
            $sql .= " $column = :$column,";
            $arguments[":$column"] = $argument;
        }
        // The concatenate operator has left us with a trailing comma.
        $sql = trim($sql, ",") . " WHERE id = :id";
        $statement = $conn->prepare($sql);

        // Bind all values and execute.
        $statement->bindValue(":id", $id);
        foreach ($arguments as $bindKey => $bindValue)
        {
            $statement->bindValue($bindKey, $bindValue);
        }
        $statement->execute();
    }

    /**
     * Delete a row in a database table.
     *
     * @param string $path
     *   Path to SQLite file.
     * @param string $table
     *   Table name.
     * @param integer $id
     *   ID of the row.
     * @param array $record
     *   Key/value array of *any* changes to write.
     */
    public static function deleteRecord($path, $table, $id)
    {
        $conn = self::getConn($path);
        $table = \SQLite3::EscapeString($table);

        $statement = $conn->prepare("DELETE FROM $table WHERE id = :id");
        $statement->bindValue(":id", $id);
        $statement->execute();
    }
}
