<?php

/**
 * @file
 * Test: TestRig\Models\RawData.
 */

namespace Tests\Models;

use TestRig\Exceptions\DatasetIntegrityException;
use TestRig\Models\Dataset;
use TestRig\Models\RawData;
use TestRig\Services\Database;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Models\RawData.
 */
class RawDataTest extends AbstractTestCase
{
    // Create and tear down database for each test.
    protected $pathToDatabase = '/tmp/for-rawdata.sqlite3';

    // Do we create a testable model?
    protected $testableClass = 'TestRig\Models\RawData';
    // And does it take the database path as __construct() argument?
    protected $testableClassNeedsDatabase = true;

    /**
     * Test: \TestRig\Models\RawData::getSummary().
     */
    public function testGetSummary()
    {
        $summary = $this->testable->getSummary();
        $this->assertArrayHasKey('entities', $summary);
        $this->assertArrayHasKey('populations', $summary);
        foreach (array('count', 'mean_ack_time', 'mean_answer_time', 'mean_routing_time', 'probability_no_ack', 'mean_extra_suppliers') as $key) {
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

        // Try to build broken recipes.
        // 1. With no questions.
        $recipeNoQuestions = array(
            'populations' => array(
                array('tier' => 1, 'number' => $numTier1),
            ),
        );
        try {
            $this->testable->populate($recipeNoQuestions);
            $this->fail('Could build recipe with no questions.');
        } catch (DatasetIntegrityException $e) {
        }
        // 2. With no populations.
        $recipeNoPopulations = array(
            'questions' => $numQuestions,
        );
        try {
            $this->testable->populate($recipeNoPopulations);
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
            $this->testable->populate($recipeBrokenTiers);
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
        $this->testable->populate($recipe);
        $summary = $this->testable->getSummary();

        // Check overall counts of entities and questions.
        $this->assertEquals($numTier1 + $numTier2, $summary['entities']['count']);
        $this->assertEquals($numQuestions, $summary['questions']['count']);

        // Populate a second time.
        $this->testable->populate($recipe);
        $summary = $this->testable->getSummary();

        // Check overall counts of entities and questions.
        $this->assertEquals(($numTier1 + $numTier2) * 2, $summary['entities']['count']);
        $this->assertEquals($numQuestions * 2, $summary['questions']['count']);

        // Check we have unique names.
        $entities = $this->testable->getEntities();
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
        $record = array('name' => 'Get Entities '.uniqid());
        Database::writeRecord($this->pathToDatabase, 'entity', $record);

        // Get entities out of database and check ours is among them.
        $entities = $this->testable->getEntities();
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
        $record = array('name' => 'Get Entities '.uniqid());
        Database::writeRecord($this->pathToDatabase, 'entity', $record);
        $tierRecord = array("entity" => $record['id'], "tier" => 5);
        Database::writeRecord($this->pathToDatabase, 'entity_tier', $tierRecord);

        // All data back.
        $allEntities = $this->testable->export(array('entity' => 'all'));
        $this->assertEquals($allEntities['entity'][0]['name'], $record['name']);

        $count = $this->testable->export(array('entity' => 'anything_else'));
        $this->assertEquals($count['entity'][0]['count'], 1);

        // Another table.
        $allTiers = $this->testable->export(array('entity_tier' => 'all'));
        $this->assertEquals($allTiers['entity_tier'][0]['tier'], $tierRecord['tier']);
    }
}
