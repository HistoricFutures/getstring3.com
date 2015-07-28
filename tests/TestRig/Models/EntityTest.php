<?php

/**
 * @file
 * Test: TestRig\Models\Entity.
 */

namespace Tests\Models;

use TestRig\Models\Entity;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Models\Entity.
 */

class EntityTest extends AbstractTestCase
{
    // Create and tear down database for each test.
    protected $pathToDatabase = "/tmp/for-entity.sqlite3";

    /**
     * Set up.
     */
    public function setUp()
    {
        parent::setUp();
        $this->model = new Entity($this->pathToDatabase);
    }

    /**
     * Test: \TestRig\Models\Entity::__construct().
     */
    public function testConstruct()
    {
        // We should always have an entity.
        $this->assertEquals(1, $this->model->data['id']);

        // Default properties.

        $this->assertNotNull($this->model->data['mean_ack_time']);
        $this->assertNotNull($this->model->data['mean_answer_time']);
        $this->assertNotNull($this->model->data['mean_routing_time']);
        $this->assertNotNull($this->model->data['mean_extra_suppliers']);
        $this->assertNotNull($this->model->data['population']);
        $this->assertNotNull($this->model->data['tier']);
        $this->assertNotNull($this->model->data['probability_no_ack']);
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
