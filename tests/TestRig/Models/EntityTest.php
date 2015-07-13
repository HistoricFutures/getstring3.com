<?php

/**
 * @file
 * Test: TestRig\Models\Entity.
 */

use TestRig\Models\Entity;
use TestRig\Services\Database;

/**
 * @class
 * Test: TestRig\Models\Entity.
 */

class EntityTest extends \PHPUnit_Framework_TestCase
{
    // Create and tear down database for each test.
    private $pathToDatabase = "/tmp/for-entity.sqlite3";
    // Database connection.
    private $conn = NULL;

    /**
     * Set up.
     */
    public function setUp()
    {
        $this->conn = Database::create($this->pathToDatabase);
        $this->model = new Entity($this->pathToDatabase);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        unlink($this->pathToDatabase);
    }

    /**
     * Test: \TestRig\Models\Entity::__construct().
     */
    public function testConstruct()
    {
        // We should always have an entity.
        $this->assertEquals(1, $this->model->data['id']);
        $this->assertNotNull($this->model->data['probability_reask']);
        $this->assertNotNull($this->model->data['mean_response_time']);
    }

    /**
     * Test: \TestRig\Models\Entity::create().
     */
    public function testCreate()
    {
        $oldName = $this->model->data['name'];

        // Create a new entity and confirm its newness.
        $this->model->create();
        $this->assertEquals(2, $this->model->data['id']);
        $this->assertNotEquals($oldName, $this->model->data['name']);
    }

    /**
     * Test: \TestRig\Models\Entity::read().
     */
    public function testRead()
    {
        $oldName = $this->model->data['name'];

        // Create a new entity and bind to it, but then re-bind to the
        // entity with ID=1.
        $this->model->create();
        $this->model->read(1);
        $this->assertEquals($oldName, $this->model->data['name']);

        // Read a record that doesn't exist.
        $this->model->read(5);
        $this->assertNull($this->model->data);
    }

    /**
     * Test: \TestRig\Models\Entity::update().
     */
    public function testUpdate()
    {
        $newName = "Test " . uniqid();

        $this->model->data['name'] = $newName;
        $this->model->update();
        $this->model->read(1);
        $this->assertEquals($newName, $this->model->data['name']);
    }

    /**
     * Test: \TestRig\Models\Entity::delete().
     */
    public function testDelete()
    {
        $this->model->delete();
        $this->model->read(1);
        $this->assertNull($this->model->data);
    }

    /**
     * Test: \TestRig\Models\Entity::getID().
     */
    public function testGetID()
    {
        $id = $this->model->getID();
        $this->assertEquals($id, $this->model->data['id']);
        $this->model->data['id'] = 5;
        $this->assertNotEquals($id, $this->model->data['id']);
    }
}
