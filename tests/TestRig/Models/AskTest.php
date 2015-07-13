<?php

/**
 * @file
 * Test: TestRig\Models\Ask.
 */

use TestRig\Models\Ask;
use TestRig\Models\Entity;
use TestRig\Services\Database;

/**
 * @class
 * Test: TestRig\Models\Ask.
 */
class AskTest extends \PHPUnit_Framework_TestCase
{
    // Create and tear down database for each test.
    private $pathToDatabase = "/tmp/for-ask.sqlite3";
    // Database connection.
    private $conn = NULL;

    /**
     * Set up.
     */
    public function setUp()
    {
        $this->conn = Database::create($this->pathToDatabase);
        $this->model = new Ask($this->pathToDatabase);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        unlink($this->pathToDatabase);
    }

    /**
     * Test: \TestRig\Models\Ask::__construct().
     */
    public function testConstruct()
    {
        // We should always have an entity.
        $this->assertEquals(1, $this->model->data['id']);
    }

    /**
     * Test: \TestRig\Models\Ask::create().
     */
    public function testCreate()
    {
        // Create a new entity and confirm its newness.
        $this->model->create();
        $this->assertEquals(2, $this->model->data['id']);


    }

    /**
     * Test: \TestRig\Models\Ask::read().
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
     * Test: \TestRig\Models\Ask::update().
     */
    public function testUpdate()
    {
        try
        {
            $this->model->update();
            $this->fail('Asks should not be updatable.');
        }
        catch (Exception $e) {}
    }

    /**
     * Test: \TestRig\Models\Ask::delete().
     */
    public function testDelete()
    {
        $this->model->delete();
        $this->model->read(1);
        $this->assertNull($this->model->data);
    }

    /**
     * Test: \TestRig\Models\Ask::getID().
     */
    public function testGetID()
    {
        $id = $this->model->getID();
        $this->assertEquals($id, $this->model->data['id']);
        $this->model->data['id'] = 5;
        $this->assertNotEquals($id, $this->model->data['id']);
    }

    /**
     * Test: \TestRig\Models\Ask::addAction().
     */
    public function testAddAction()
    {
        $from = new Entity($this->pathToDatabase);
        $to = new Entity($this->pathToDatabase);
        $action = array(
            'ask' => $this->model->getID(),
            'entity_from' => $from->getID(),
            'entity_to' => $from->getID(),
            'time_taken' => 50,
        );
        $this->model->addAction($action);

        // ID should be retrieved.
        $this->assertArrayHasKey('id', $action);
        // And action should be on the list.
        $actions = $this->model->getActions();
        $this->assertEquals($action['id'], $actions[0]['id']);

        // Actions should be preserved across reloads.
        $this->model->read(1);
        $actions = $this->model->getActions();
        $this->assertEquals($action['id'], $actions[0]['id']);
        // But not if the reload is of an ask that doesn't exist!
        $this->model->read(5);
        $this->assertEmpty($this->model->getActions());
    }
    /**
     * Test: \TestRig\Models\Ask::getActions().
     */
    public function testGetActions()
    {
        // Confirm actions is at least an array.
        $this->assertTrue(is_array($this->model->getActions()));
        $this->assertEmpty($this->model->getActions());
    }
}
