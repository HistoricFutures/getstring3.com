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
    private static $dataset = NULL;
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
        $number = 50;

        // Set up a fake BOP and wrap the database in RawData.
        $bop = array("populations" => array(array("number" => $number)));
        $rawData = new RawData($this->pathToDatabase);

        // Every time we populate, total should increase by $number.
        $rawData->populate($bop);
        $summary = $rawData->getSummary();
        $this->assertEquals($number, $summary["entities"]["count"]);
        $rawData->populate($bop);
        $summary = $rawData->getSummary();
        $this->assertEquals($number * 2, $summary["entities"]["count"]);
    }
}
