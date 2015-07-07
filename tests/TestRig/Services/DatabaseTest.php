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
        $path = "/tmp/testrig-" . getmypid() . "-create.sqlite3";
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
            unlink($path);
            throw $e;
        }

        unlink($path);
    }

    /**
     * Test: TestRig\Services\Database::getTableCount().
     */
    public function testGetTableCount()
    {
        $path = "/tmp/testrig-" . getmypid() . "-gettablecount.sqlite3";
        Database::create($path);

        $this->assertEquals(Database::getTableCount($path, "entity"), 0);
        try
        {
            Database::getTableCount($path, "not_a_table");
        }
        catch (Exception $e)
        {
            if (strpos($e->getMessage(), "no such table: not_a_table") === FALSE)
            {
                unlink($path);
                $this->fail("Can get table count from a table that doesn't exist.");
            }
        }

        unlink($path);
    }

    /**
     * Test: TestRig\Services\Database::getWriteRecord().
     */
    public function testWriteRecord()
    {
        $path = "/tmp/testrig-" . getmypid() . "-writerecord.sqlite3";
        Database::create($path);

        // Write record and ensure we get an ID back.
        $record = array("name" => "Test " . uniqid());
        Database::writeRecord($path, "entity", $record);
        $this->assertEquals(1, $record["id"]);

        // Get the row from the database and check names match.
        $results = Database::getConn($path)->query("SELECT * FROM entity WHERE id = 1");
        $row = $results->fetchArray();
        $this->assertEquals($record["name"], $row["name"]);

        unlink($path);
    }
}
