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
        $this->entity = new Entity($this->pathToDatabase);
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
        $this->assertEquals(1, $this->entity->data['id']);
        $this->assertNotNull($this->entity->data['probability_reask']);
        $this->assertNotNull($this->entity->data['mean_response_time']);
    }

    /**
     * Test: \TestRig\Models\Entity::create().
     */
    public function testCreate()
    {
        $oldName = $this->entity->data['name'];

        // Create a new entity and confirm its newness.
        $this->entity->create();
        $this->assertEquals(2, $this->entity->data['id']);
        $this->assertNotEquals($oldName, $this->entity->data['name']);
    }

    /**
     * Test: \TestRig\Models\Entity::read().
     */
    public function testRead()
    {
        $oldName = $this->entity->data['name'];

        // Create a new entity and bind to it, but then re-bind to the
        // entity with ID=1.
        $this->entity->create();
        $this->entity->read(1);
        $this->assertEquals($oldName, $this->entity->data['name']);

        // Read a record that doesn't exist.
        $this->entity->read(5);
        $this->assertNull($this->entity->data);
    }

    /**
     * Test: \TestRig\Models\Entity::update().
     */
    public function testUpdate()
    {
        $newName = "Test " . uniqid();

        $this->entity->data['name'] = $newName;
        $this->entity->update();
        $this->entity->read(1);
        $this->assertEquals($newName, $this->entity->data['name']);
    }

    /**
     * Test: \TestRig\Models\Entity::delete().
     */
    public function testDelete()
    {
        $this->entity->delete();
        $this->entity->read(1);
        $this->assertNull($this->entity->data);
    }

    /**
     * Test: \TestRig\Models\Entity::getID().
     */
    public function testGetID()
    {
        $id = $this->entity->getID();
        $this->assertEquals($id, $this->entity->data['id']);
        $this->entity->data['id'] = 5;
        $this->assertNotEquals($id, $this->entity->data['id']);
    }
}
