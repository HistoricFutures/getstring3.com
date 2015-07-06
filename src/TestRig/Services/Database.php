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
}

