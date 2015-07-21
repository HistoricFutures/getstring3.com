<?php

/**
 * @file
 * Test: TestRig\Models\Question.
 */

use TestRig\Models\Question;
use TestRig\Models\Entity;
use TestRig\Services\Database;

/**
 * @class
 * Test: TestRig\Models\Question.
 */
class QuestionTest extends \PHPUnit_Framework_TestCase
{
    // Create and tear down database for each test.
    private $pathToDatabase = "/tmp/for-question.sqlite3";
    // Database connection.
    private $conn = null;

    /**
     * Set up.
     */
    public function setUp()
    {
        $this->conn = Database::create($this->pathToDatabase);
        $this->model = new Question($this->pathToDatabase);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        unlink($this->pathToDatabase);
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
        } catch (Exception $e) {
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
     * Test: \TestRig\Models\Question::addAction().
     */
    public function testAddAction()
    {
        $from = new Entity($this->pathToDatabase);
        $to = new Entity($this->pathToDatabase);
        $action = array(
            'question' => $this->model->getID(),
            'entity_from' => $from->getID(),
            'entity_to' => $from->getID(),
            'time_start' => 50,
            'time_stop' => 100,
        );
        $this->model->addAction($action);

        // ID should be retrieved.
        $this->assertArrayHasKey('id', $action);
        // And action should be on the list.
        $actions = $this->model->getActions();
        $this->assertEquals($action['id'], $actions[0]['id']);
        $this->assertEquals($this->model->getID(), $actions[0]['question']);

        // Actions should be preserved across reloads.
        $this->model->read(1);
        $actions = $this->model->getActions();
        $this->assertEquals($action['id'], $actions[0]['id']);
        // But not if the reload is of an question that doesn't exist!
        $this->model->read(5);
        $this->assertEmpty($this->model->getActions());
    }

    /**
     * Test: \TestRig\Models\Question::getActions().
     */
    public function testGetActions()
    {
        // Confirm actions is at least an array.
        $this->assertTrue(is_array($this->model->getActions()));
        $this->assertEmpty($this->model->getActions());
    }

    /**
     * Test: \TestRig\Models\Question::generateActions().
     */
    public function testGenerateActions()
    {
        // Create a few new entities.
        for ($i = 0; $i < 5; $i++) {
            new Entity($this->pathToDatabase);
        }
        // Create a new question chain.
        $this->model->generateActions();

        $this->assertNotEmpty($this->model->getActions());
    }
}
