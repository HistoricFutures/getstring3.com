<?php

/**
 * @file
 * Test: TestRig\Models\RawData.
 */

use TestRig\Models\Dataset;
use TestRig\Models\RawData;
use TestRig\Services\Database;

/**
 * @class
 * Test: TestRig\Models\RawData.
 */
class RawDataTest extends \PHPUnit_Framework_TestCase
{
    // Dataset helper created in ::setUpBeforeClass().
    private static $dataset = null;
    // Create and tear down database for each test.
    private $pathToDatabase = "/tmp/for-rawdata.sqlite3";

    /**
     * Set up before class.
     *
     * Create Dataset helper.
     */
    public static function setUpBeforeClass()
    {
        self::$dataset = new Dataset();
    }

    /**
     * Set up.
     */
    public function setUp()
    {
        Database::create($this->pathToDatabase);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        unlink($this->pathToDatabase);
    }

    /**
     * Test: \TestRig\Models\RawData::getSummary().
     */
    public function testGetSummary()
    {
        $summary = (new RawData($this->pathToDatabase))->getSummary();
        $this->assertArrayHasKey("entities", $summary);
        $this->assertArrayHasKey("count", $summary["entities"]);
    }

    /**
     * Test: \TestRig\Models\RawData::populate().
     */
    public function testPopulate()
    {
        $numEntities = 15;
        $numAsks = 50;

        // Set up a fake recipe and wrap the database in RawData.
        $recipe = array(
            "populations" => array(array("number" => $numEntities)),
            "asks" => $numAsks,
        );
        $rawData = new RawData($this->pathToDatabase);

        // Every time we populate, total should increase by $numEntities.
        $rawData->populate($recipe);
        $summary = $rawData->getSummary();

        // Check overall counts of entities and asks.
        $this->assertEquals($numEntities, $summary["entities"]["count"]);
        $this->assertEquals($numAsks, $summary["asks"]["count"]);

        // Populate a second time.
        $rawData->populate($recipe);
        $summary = $rawData->getSummary();

        // Check overall counts of entities and asks.
        $this->assertEquals($numEntities * 2, $summary["entities"]["count"]);
        $this->assertEquals($numAsks * 2, $summary["asks"]["count"]);

        // Check we have unique names.
        $entities = $rawData->getEntities();
        $this->assertNotEquals($entities[1]['name'], $entities[2]['name']);
        // Check we have columns for mean response time, reask probability etc.
        $this->assertArrayHasKey('mean_response_time', $entities[1]);
        $this->assertArrayHasKey('probability_reask', $entities[1]);
        $this->assertNotNull($entities[1]['mean_response_time']);
        $this->assertNotNull($entities[1]['probability_reask']);

        // Check ask IDs saved.
        $conn = Database::getConn($this->pathToDatabase);
        $results = $conn->query("SELECT * FROM action WHERE ask IS NULL");
        while ($row = $results->fetchArray()) {
            $this->fail("Saved an action where the ask ID is null.");
        }
    }

    /**
     * Test: \TestRig\Models\RawData::getEntities().
     */
    public function testGetEntities()
    {
        // Attach to the database and insert an entity.
        $rawData = new RawData($this->pathToDatabase);
        $record = array("name" => "Get Entities " . uniqid());
        Database::writeRecord($this->pathToDatabase, "entity", $record);

        // Get entities out of database and check ours is among them.
        $entities = (new RawData($this->pathToDatabase))->getEntities();
        $this->assertArrayHasKey($record["id"], $entities);
        $this->assertEquals($record["name"], $entities[$record["id"]]["name"]);

        // We don't want the columnar format along with associative.
        $this->assertFalse(isset($entities[$record["id"]][0]));
    }

    /**
     * Test: \TestRig\Models\RawData::export().
     */
    public function testExport()
    {
        // Attach to the database and insert an entity.
        $rawData = new RawData($this->pathToDatabase);
        $record = array('name' => 'Get Entities ' . uniqid());
        Database::writeRecord($this->pathToDatabase, 'entity', $record);

        // All data back.
        $allEntities = $rawData->export(array('entity' => 'all'));
        $this->assertEquals($allEntities['entity'][0]['name'], $record['name']);

        $count = $rawData->export(array('entity' => 'anything_else'));
        $this->assertEquals($count['entity'][0]['count'], 1);
    }
}
