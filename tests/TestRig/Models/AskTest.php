<?php

/**
 * @file
 * Test: TestRig\Models\Ask.
 */

use TestRig\Models\Ask;
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
     * Test: \TestRig\Models\Entity::__construct().
     */
    public function testConstruct()
    {
        // We should always have an entity.
        $this->assertEquals(1, $this->model->data['id']);
    }

    /**
     * Test: \TestRig\Models\Entity::create().
     */
    public function testCreate()
    {
        // Create a new entity and confirm its newness.
        $this->model->create();
        $this->assertEquals(2, $this->model->data['id']);
    }

    /**
     * Test: \TestRig\Models\Entity::read().
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
     * Test: \TestRig\Models\Entity::update().
     */
    public function testUpdate()
    {
        $this->marktestincomplete('update should perhaps be disabled for ask?');
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
