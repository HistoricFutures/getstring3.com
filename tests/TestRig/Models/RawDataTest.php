<?php

/**
 * @file
 * Test: TestRig\Models\RawData.
 */
use TestRig\Exceptions\DatasetIntegrityException;
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
    private $pathToDatabase = '/tmp/for-rawdata.sqlite3';

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
        $this->assertArrayHasKey('entities', $summary);
        foreach (array('count', 'mean_ack_time', 'mean_answer_time', 'mean_routing_time', 'probability_no_ack') as $key) {
            $this->assertArrayHasKey($key, $summary['entities']);
        }
    }

    /**
     * Test: \TestRig\Models\RawData::populate().
     */
    public function testPopulate()
    {
        $numTier1 = 15;
        $numTier2 = 20;
        $numQuestions = 50;

        // Raw data bucket.
        $rawData = new RawData($this->pathToDatabase);

        // Try to build broken recipes.
        // 1. With no questions.
        $recipeNoQuestions = array(
            'populations' => array(
                array('tier' => 1, 'number' => $numTier1),
            ),
        );
        try {
            $rawData->populate($recipeNoQuestions);
            $this->fail('Could build recipe with no questions.');
        } catch (DatasetIntegrityException $e) {
        }
        // 2. With no populations.
        $recipeNoPopulations = array(
            'questions' => $numQuestions,
        );
        try {
            $rawData->populate($recipeNoPopulations);
            $this->fail('Could build recipe with no populations.');
        } catch (DatasetIntegrityException $e) {
        }
        // 3. With uncontiguous tiers.
        $recipeBrokenTiers = array(
            'populations' => array(
                array('tier' => 2, 'number' => $numTier2),
            ),
            'questions' => $numQuestions,
        );
        try {
            $rawData->populate($recipeBrokenTiers);
            $this->fail('Could build recipe with broken tiers.');
        } catch (DatasetIntegrityException $e) {
        }

        // Set up a good recipe and wrap the database in RawData.
        $recipe = array(
            'populations' => array(
                array('tier' => 1, 'number' => $numTier1),
                array('tier' => 2, 'number' => $numTier2),
            ),
            'questions' => $numQuestions,
        );

        // Every time we populate, total should increase by two numbers.
        $rawData->populate($recipe);
        $summary = $rawData->getSummary();

        // Check overall counts of entities and questions.
        $this->assertEquals($numTier1 + $numTier2, $summary['entities']['count']);
        $this->assertEquals($numQuestions, $summary['questions']['count']);

        // Populate a second time.
        $rawData->populate($recipe);
        $summary = $rawData->getSummary();

        // Check overall counts of entities and questions.
        $this->assertEquals(($numTier1 + $numTier2) * 2, $summary['entities']['count']);
        $this->assertEquals($numQuestions * 2, $summary['questions']['count']);

        // Check we have unique names.
        $entities = $rawData->getEntities();
        $this->assertNotEquals($entities[1]['name'], $entities[2]['name']);
        // Check we have columns for mean response time, no-ack probability etc.
        foreach (array('mean_ack_time', 'mean_answer_time', 'mean_routing_time', 'probability_no_ack') as $key) {
            $this->assertArrayHasKey($key, $entities[1]);
            $this->assertNotNull($entities[1][$key]);
        }

        // Check question IDs saved.
        $conn = Database::getConn($this->pathToDatabase);
        $results = $conn->query('SELECT * FROM ask WHERE question IS NULL');
        while ($row = $results->fetchArray()) {
            $this->fail('Saved an action where the question ID is null.');
        }
    }

    /**
     * Test: \TestRig\Models\RawData::getEntities().
     */
    public function testGetEntities()
    {
        // Attach to the database and insert an entity.
        $rawData = new RawData($this->pathToDatabase);
        $record = array('name' => 'Get Entities '.uniqid());
        Database::writeRecord($this->pathToDatabase, 'entity', $record);

        // Get entities out of database and check ours is among them.
        $entities = (new RawData($this->pathToDatabase))->getEntities();
        $this->assertArrayHasKey($record['id'], $entities);
        $this->assertEquals($record['name'], $entities[$record['id']]['name']);

        // We don't want the columnar format along with associative.
        $this->assertFalse(isset($entities[$record['id']][0]));
    }

    /**
     * Test: \TestRig\Models\RawData::export().
     */
    public function testExport()
    {
        // Attach to the database and insert an entity.
        $rawData = new RawData($this->pathToDatabase);
        $record = array('name' => 'Get Entities '.uniqid());
        Database::writeRecord($this->pathToDatabase, 'entity', $record);

        // All data back.
        $allEntities = $rawData->export(array('entity' => 'all'));
        $this->assertEquals($allEntities['entity'][0]['name'], $record['name']);

        $count = $rawData->export(array('entity' => 'anything_else'));
        $this->assertEquals($count['entity'][0]['count'], 1);
    }
}
