<?php

/**
 * @file
 * Database library calls.
 */

namespace TestRig\Services;

use TestRig\Exceptions\MissingDatasetFileException;
use TestRig\Exceptions\MissingTableException;

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
        if (file_exists($path)) {
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
        set_error_handler(array('TestRig\Exceptions\MissingTableException', 'errorHandler'));
        $results = $conn->query("SELECT COUNT(*) AS result FROM $table");
        restore_error_handler();

        while ($row = $results->fetchArray()) {
            return $row['result'];
        }
    }

    /**
     * Get arbitrary aggregate data on a table.
     *
     * @param string $path
     *   Path to the SQLite3 file.
     * @param string $table
     *   Table name (unescaped).
     * @param string $aggregate
     *   Aggregate function (unescaped).
     * @param string $column
     *   Column to run aggregate on (unescaped).
     * @param array $groupBy = null
     *   Column(s) to group by before aggregation.
     * @return mixed
     *   Scalar string or integer reflecting aggregate function.
     */
    public static function getTableAggregate($path, $table, $aggregate, $column, $groupBy = null)
    {
        // Get connection and escape arguments.
        $conn = self::getConn($path);
        $table = \SQLite3::escapeString($table);
        $aggregate = \SQLite3::escapeString($aggregate);
        $column = \SQLite3::escapeString($column);

        // Make query and return first value we find.
        $sql = "SELECT $aggregate($column) AS result FROM $table";
        if ($groupBy) {
            $sql .= " GROUP BY " . implode(", ", $groupBy);
        }
        $results = $conn->query($sql);
        while ($row = $results->fetchArray()) {
            return $row['result'];
        }
    }

    /**
     * Get all rows matching a criteria.
     *
     * @param string $path
     *   Path to SQLite database.
     * @param string $table
     *   Table in database.
     * @param array $filters = array()
     *   Optional filters (if not set, will return whole table!)
     * @param string $orderBy = 'id'
     *   Order by this column.
     * @return array
     *   Array of results in ID order.
     */
    public static function getRowsWhere($path, $table, $filters = array(), $orderBy = 'id')
    {
        $conn = self::getConn($path);
        $table = \SQLite3::EscapeString($table);
        $orderBy = \SQLite3::EscapeString($orderBy);

        // Assemble SQL including WHERE columns.
        $sql = "SELECT * FROM $table ";
        $arguments = array();
        if ($filters) {
            $where = ' WHERE ';
            foreach ($filters as $column => $argument) {
                $column = \SQLite3::EscapeString($column);
                $where .=  "$column = :$column,";
                $arguments[":$column"] = $argument;
            }
            $sql .= trim($where, ',');
        }
        $sql .= " ORDER BY $orderBy";

        // Prepare statement and bind variables.
        $results = self::returnStatement($conn, $sql, $arguments)->execute();

        // Read results into an array and return.
        $rows = array();
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
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
        if (file_exists($path) || ($flags & SQLITE3_OPEN_CREATE)) {
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
     * @param bool $hasId = true
     *   Row has an ID field and therefore the ID should be returned.
     * @return integer or null
     *   Integer ID if the row has an ID; otherwise null.
     */
    public static function writeRecord($path, $table, &$record, $hasId = true)
    {
        $conn = self::getConn($path);
        $table = \SQLite3::EscapeString($table);

        // Ensure columns and arguments match up.
        $columns = array();
        $arguments = array();
        foreach ($record as $column => $argument) {
            $column = \SQLite3::EscapeString($column);
            $columns[] = $column;
            $arguments[":$column"] = $argument;
        }

        // Create SQL based on $columns, sneaking a ":" in there for VALUES.
        // Cope with situation when there's no data and all values are default.
        if ($columns) {
            $sql = "INSERT INTO $table (" . implode(", ", $columns) .
                ") VALUES(:" . implode(", :", $columns) . ");";
        } else {
            $sql = "INSERT INTO $table DEFAULT VALUES;";
        }

        $statement = $conn->prepare($sql);

        // Bind all values and execute.
        foreach ($arguments as $bindKey => $bindValue) {
            $statement->bindValue($bindKey, $bindValue);
        }
        $statement->execute();

        // Should we be expecting an ID from this row?
        if (!$hasId) {
            return;
        }

        // Using same connection, inject the autoincrement ID into $record.
        $statement = $conn->prepare("SELECT last_insert_rowid() AS id;");
        $results = $statement->execute();
        while ($row = $results->fetchArray()) {
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

        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
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
        foreach ($record as $column => $argument) {
            $column = \SQLite3::EscapeString($column);
            $sql .= " $column = :$column,";
            $arguments[":$column"] = $argument;
        }
        // The concatenate operator has left us with a trailing comma.
        $sql = trim($sql, ",") . " WHERE id = :id";
        $statement = $conn->prepare($sql);

        // Bind all values and execute.
        $statement->bindValue(":id", $id);
        foreach ($arguments as $bindKey => $bindValue) {
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

        self::returnStatement(
            $conn,
            "DELETE FROM $table WHERE id = :id",
            array(":id" => $id)
        )->execute();
    }

    /**
     * Delete rows in a database table matching conditions.
     *
     * @param string $path
     *   Path to SQLite file.
     * @param string $table
     *   Table name.
     * @param array $wheres = array()
     *   Key/value array of *any* changes to write.
     */
    public static function deleteWhere($path, $table, $wheres = array())
    {
        $conn = self::getConn($path);
        $table = \SQLite3::EscapeString($table);

        $sql = "DELETE FROM $table WHERE 1 = 1 ";
        $arguments = array();
        foreach ($wheres as $column => $rawValue) {
            $column = \SQLite3::EscapeString($column);
            $sql .= " AND $column = :$column ";
            $arguments[":$column"] = $rawValue;
        }

        self::returnStatement($conn, $sql, $arguments)->execute();
    }

    /**
     * Private: pepare a statement with arguments.
     *
     * @param \SQLite3 $conn
     *   Database connection.
     * @param string $sql
     *   String SQL query with ":foo" placeholders for arguments.
     * @param array $arguments
     *   Optional array of arguments to match the placeholders.
     * @return \SQLite3Stmt
     *   Prepared statement, ready for execution.
     */
    public static function returnStatement($conn, $sql, $arguments = array())
    {
        $statement = $conn->prepare($sql);
        // Bind all values.
        foreach ($arguments as $bindKey => $bindValue) {
            $statement->bindValue($bindKey, $bindValue);
        }
        return $statement;
    }
}
