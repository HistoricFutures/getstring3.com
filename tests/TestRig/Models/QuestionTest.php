<?php

/**
 * @file
 * Test: TestRig\Models\Question.
 */

namespace Tests\Models;

use TestRig\Models\Entity;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Models\Question.
 */
class QuestionTest extends AbstractTestCase
{
    // Create and tear down database for each test.
    protected $pathToDatabase = "/tmp/for-question.sqlite3";

    // Do we create a testable model?
    protected $testableClass = 'TestRig\Models\Question';
    // And does it take the database path as __construct() argument?
    protected $testableClassNeedsDatabase = true;

    /**
     * Test: \TestRig\Models\Question::__construct().
     */
    public function testConstruct()
    {
        // We should always have an entity.
        $this->assertEquals(1, $this->testable->data['id']);
    }

    /**
     * Test: \TestRig\Models\Question::create().
     */
    public function testCreate()
    {
        // Create a new entity and confirm its newness.
        $this->testable->create();
        $this->assertEquals(2, $this->testable->data['id']);
    }

    /**
     * Test: \TestRig\Models\Question::read().
     */
    public function testRead()
    {
        // Create a new entity and bind to it, but then re-bind to the
        // entity with ID=1.
        $this->testable->create();
        $this->testable->read(1);

        // Read a record that doesn't exist.
        $this->testable->read(5);
        $this->assertNull($this->testable->data);
    }

    /**
     * Test: \TestRig\Models\Question::update().
     */
    public function testUpdate()
    {
        try {
            $this->testable->update();
            $this->fail('Questions should not be updatable.');
        } catch (\Exception $e) {
        }
    }

    /**
     * Test: \TestRig\Models\Question::delete().
     */
    public function testDelete()
    {
        $this->testable->delete();
        $this->testable->read(1);
        $this->assertNull($this->testable->data);
    }

    /**
     * Test: \TestRig\Models\Question::getID().
     */
    public function testGetID()
    {
        $id = $this->testable->getID();
        $this->assertEquals($id, $this->testable->data['id']);
        $this->testable->data['id'] = 5;
        $this->assertNotEquals($id, $this->testable->data['id']);
    }

    /**
     * Test: \TestRig\Models\Question::addAsk().
     */
    public function testAddAsk()
    {
        $from = new Entity($this->pathToDatabase);
        $to = new Entity($this->pathToDatabase);
        $ask = array(
            'question' => $this->testable->getID(),
            'entity_from' => $from->getID(),
            'entity_to' => $from->getID(),
            'time_start' => 50,
            'time_ack' => 100,
        );
        $this->testable->addAsk($ask);

        // ID should be retrieved.
        $this->assertArrayHasKey('id', $ask);
        // And ask should be on the list.
        $asks = $this->testable->getAsks();
        $this->assertEquals($ask['id'], $asks[0]['id']);
        $this->assertEquals($this->testable->getID(), $asks[0]['question']);

        // Asks should be preserved across reloads.
        $this->testable->read(1);
        $asks = $this->testable->getAsks();
        $this->assertEquals($ask['id'], $asks[0]['id']);
        // But not if the reload is of an question that doesn't exist!
        $this->testable->read(5);
        $this->assertEmpty($this->testable->getAsks());
    }

    /**
     * Test: \TestRig\Models\Question::getAsks().
     */
    public function testGetAsks()
    {
        // Confirm ask is at least an array.
        $this->assertTrue(is_array($this->testable->getAsks()));
        $this->assertEmpty($this->testable->getAsks());
    }

    /**
     * Test: \TestRig\Models\Question::generateAsks().
     */
    public function testGenerateAsks()
    {
        // Create a few new entities.
        for ($i = 1; $i <= 5; $i++) {
            new Entity($this->pathToDatabase, null, array("tiers" => [$i]));
        }
        // Create a new question chain.
        $this->testable->generateAsks();
        $asks = $this->testable->getAsks();

        // Ensure we have at least one ask.
        $this->assertNotEmpty($asks);

        // Ensure that asks never go backwards in terms of tiers.
        // For five linear entities that means always forwards.
        $tier = 1;
        foreach ($asks as $ask) {
            $entity = new Entity($this->pathToDatabase, $ask['entity_from']);
            $this->assertEquals([$tier], $entity->data['tiers'], "Expected tier [$tier]; got tiers [" . implode(", ", $entity->data['tiers']) . ']');
            $tier++;
        }
    }
}
