<?php

/**
 * @file
 * Test: TestRig\Models\Question.
 */

namespace Tests\Models;

use TestRig\Models\Question;
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

    /**
     * Set up.
     */
    public function setUp()
    {
        parent::setUp();
        $this->model = new Question($this->pathToDatabase);
    }

    /**
     * Test: \TestRig\Models\Question::__construct().
     */
    public function testConstruct()
    {
        // We should always have an entity.
        $this->assertEquals(1, $this->model->data['id']);
    }

    /**
     * Test: \TestRig\Models\Question::create().
     */
    public function testCreate()
    {
        // Create a new entity and confirm its newness.
        $this->model->create();
        $this->assertEquals(2, $this->model->data['id']);
    }

    /**
     * Test: \TestRig\Models\Question::read().
     */
    public function testRead()
    {
        // Create a new entity and bind to it, but then re-bind to the
        // entity with ID=1.
        $this->model->create();
        $this->model->read(1);

        // Read a record that doesn't exist.
        $this->model->read(5);
        $this->assertNull($this->model->data);
    }

    /**
     * Test: \TestRig\Models\Question::update().
     */
    public function testUpdate()
    {
        try {
            $this->model->update();
            $this->fail('Questions should not be updatable.');
        } catch (\Exception $e) {
        }
    }

    /**
     * Test: \TestRig\Models\Question::delete().
     */
    public function testDelete()
    {
        $this->model->delete();
        $this->model->read(1);
        $this->assertNull($this->model->data);
    }

    /**
     * Test: \TestRig\Models\Question::getID().
     */
    public function testGetID()
    {
        $id = $this->model->getID();
        $this->assertEquals($id, $this->model->data['id']);
        $this->model->data['id'] = 5;
        $this->assertNotEquals($id, $this->model->data['id']);
    }

    /**
     * Test: \TestRig\Models\Question::addAsk().
     */
    public function testAddAsk()
    {
        $from = new Entity($this->pathToDatabase);
        $to = new Entity($this->pathToDatabase);
        $ask = array(
            'question' => $this->model->getID(),
            'entity_from' => $from->getID(),
            'entity_to' => $from->getID(),
            'time_start' => 50,
            'time_ack' => 100,
        );
        $this->model->addAsk($ask);

        // ID should be retrieved.
        $this->assertArrayHasKey('id', $ask);
        // And ask should be on the list.
        $asks = $this->model->getAsks();
        $this->assertEquals($ask['id'], $asks[0]['id']);
        $this->assertEquals($this->model->getID(), $asks[0]['question']);

        // Asks should be preserved across reloads.
        $this->model->read(1);
        $asks = $this->model->getAsks();
        $this->assertEquals($ask['id'], $asks[0]['id']);
        // But not if the reload is of an question that doesn't exist!
        $this->model->read(5);
        $this->assertEmpty($this->model->getAsks());
    }

    /**
     * Test: \TestRig\Models\Question::getAsks().
     */
    public function testGetAsks()
    {
        // Confirm ask is at least an array.
        $this->assertTrue(is_array($this->model->getAsks()));
        $this->assertEmpty($this->model->getAsks());
    }

    /**
     * Test: \TestRig\Models\Question::generateAsks().
     */
    public function testGenerateAsks()
    {
        // Create a few new entities.
        for ($i = 1; $i <= 5; $i++) {
            new Entity($this->pathToDatabase, null, array("tier" => $i));
        }
        // Create a new question chain.
        $this->model->generateAsks();
        $asks = $this->model->getAsks();

        // Ensure we have at least one ask.
        $this->assertNotEmpty($asks);

        // Ensure that asks never go backwards in terms of tiers.
        // For five linear entities that means always forwards.
        $tier = 1;
        foreach ($asks as $ask) {
            $entity = new Entity($this->pathToDatabase, $ask['entity_from']);
            $this->assertEquals($tier, $entity->data['tier'], "Expected tier $tier; got tier {$entity->data['tier']}");
            $tier++;
        }
    }
}
