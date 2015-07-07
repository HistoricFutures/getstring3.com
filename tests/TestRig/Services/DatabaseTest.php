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

    // Path to temporary database file.
    private $path = NULL;

    /**
     * Set up.
     */
    public function setUp()
    {
        $this->path = "/tmp/testrig-" . getmypid() . ".sqlite3";
        Database::create($this->path);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        unlink($this->path);
    }

    /**
     * Test: TestRig\Services\Database:create().
     */
    public function testCreate()
    {
        if (!class_exists('\SQLite3'))
        {
            $this->fail("php5-sqlite must be installed via PECL, apt-get etc.");
        }

        // Check database even exists.
        $this->assertFileExists($this->path);

        // Now connect to it and look for the entity table.
        $conn = Database::getConn($this->path);
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
    }

    /**
     * Test: TestRig\Services\Database::getTableCount().
     */
    public function testGetTableCount()
    {
        $this->assertEquals(Database::getTableCount($this->path, "entity"), 0);
        try
        {
            Database::getTableCount($this->path, "not_a_table");
        }
        catch (Exception $e)
        {
            if (strpos($e->getMessage(), "no such table: not_a_table") === FALSE)
            {
                $this->fail("Can get table count from a table that doesn't exist.");
            }
        }
    }

    /**
     * Test: TestRig\Services\Database::getWriteRecord().
     */
    public function testWriteRecord()
    {
        // Write record and ensure we get an ID back.
        $record = array("name" => "Test " . uniqid());
        Database::writeRecord($this->path, "entity", $record);
        $this->assertEquals(1, $record["id"]);

        // Get the row from the database and check names match.
        $results = Database::getConn($this->path)->query("SELECT * FROM entity WHERE id = 1");
        $row = $results->fetchArray();
        $this->assertEquals($record["name"], $row["name"]);
    }
}
