<?php

/**
 * @file
 * Test: TestRig\Services\Database.
 */

use TestRig\Services\Database;

/**
 * @class
 * Test: TestRig\Services\Database.
 */
class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test: TestRig\Services\Database:create().
     */
    public function testCreate()
    {
        if (!class_exists('\SQLite3'))
        {
            $this->fail("php5-sqlite must be installed via PECL, apt-get etc.");
        }
        $path = "/tmp/testrig-" . getmypid() . ".sqlite3";
        Database::create($path);

        // Check database even exists.
        $this->assertFileExists($path);

        // Now connect to it and look for the entity table.
        $conn = Database::getConn($path);
        try
        {
            $conn->exec("SELECT * FROM entity");
        }
        catch (Exception $e)
        {
            if (strpos($e->getMessage(), "no such table: entity") !== FALSE)
            {
                $this->fail("Creating database doesn't create table entity.");
            }
            throw $e;
        }

        unlink($path);
    }
}
