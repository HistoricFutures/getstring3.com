<?php

/**
 * @file
 * Test: TestRig\Models\Entity.
 */

namespace Tests\Models;

use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Models\Entity.
 */
class EntityTest extends AbstractTestCase
{
    // Create and tear down database for each test.
    protected $pathToDatabase = "/tmp/for-entity.sqlite3";

    // Do we create a testable model?
    protected $testableClass = 'TestRig\Models\Entity';
    // And does it take the database path as __construct() argument?
    protected $testableClassNeedsDatabase = true;

    /**
     * Test: \TestRig\Models\Entity::__construct().
     */
    public function testConstruct()
    {
        // We should always have an entity.
        $this->assertEquals(1, $this->testable->data['id']);

        // Default properties.

        $this->assertNotNull($this->testable->data['mean_ack_time']);
        $this->assertNotNull($this->testable->data['mean_answer_time']);
        $this->assertNotNull($this->testable->data['mean_routing_time']);
        $this->assertNotNull($this->testable->data['mean_extra_suppliers']);
        $this->assertNotNull($this->testable->data['population']);
        $this->assertNotNull($this->testable->data['tiers']);
        $this->assertNotNull($this->testable->data['probability_no_ack']);
    }

    /**
     * Test: \TestRig\Models\Entity::create().
     */
    public function testCreate()
    {
        $oldName = $this->testable->data['name'];

        // Create a new entity and confirm its newness.
        $this->testable->create();
        $this->assertEquals(2, $this->testable->data['id']);
        $this->assertNotEquals($oldName, $this->testable->data['name']);

        // Test probability_is_sourcing and effect on is_sourcing.
        $this->assertFalse($this->testable->data['is_sourcing']);
        $this->testable->create(array('probability_is_sourcing' => 1));
        $this->assertTrue($this->testable->data['is_sourcing']);

        // Reload and confirm SQLite3's integer-y-ness "booleans".
        $this->testable->read($this->testable->getID());
        $this->assertSame(1, $this->testable->data['is_sourcing']);

        // Tiers should be an array.
        $this->assertEquals(1, $this->testable->data['tiers'][0]);
    }

    /**
     * Test: \TestRig\Models\Entity::read().
     */
    public function testRead()
    {
        $oldName = $this->testable->data['name'];

        // Create a new entity and bind to it, but then re-bind to the
        // entity with ID=1.
        $this->testable->create();
        $this->testable->read(1);
        $this->assertEquals($oldName, $this->testable->data['name']);

        // Read a record that doesn't exist.
        $this->testable->read(200);
        $this->assertNull($this->testable->data);
    }

    /**
     * Test: \TestRig\Models\Entity::update().
     */
    public function testUpdate()
    {
        $newName = "Test " . uniqid();

        $this->testable->data['name'] = $newName;
        $this->testable->update();
        $this->testable->read(1);
        $this->assertEquals($newName, $this->testable->data['name']);
    }

    /**
     * Test: \TestRig\Models\Entity::delete().
     */
    public function testDelete()
    {
        $this->testable->delete();
        $this->testable->read(1);
        $this->assertNull($this->testable->data);
    }

    /**
     * Test: \TestRig\Models\Entity::getID().
     */
    public function testGetID()
    {
        $id = $this->testable->getID();
        $this->assertEquals($id, $this->testable->data['id']);
        $this->testable->data['id'] = 5;
        $this->assertNotEquals($id, $this->testable->data['id']);
    }

    /**
     * Test: \TestRig\Models\Entity::generateSupplierPool().
     */
    public function testGenerateSupplierPool()
    {
        // Create a new entity.
        $this->testable->create(['supplier_pool' => 1]);

        // Generate its supplier pool.
        $this->testable->generateSupplierPool();

        // It must have entity ID=1 in its pool.
        $pool = $this->testable->getSupplierPool();
        $this->assertEquals([1], $pool);

        // Re-read data and check it persists.
        $this->testable->read(1);
        $pool = $this->testable->getSupplierPool();
        $this->assertEquals([], $pool);
        $this->testable->read(2);
        $pool = $this->testable->getSupplierPool();
        $this->assertEquals([1], $pool);
    }

    /**
     * Test: \TestRig\Models\Entity::getSupplierPool().
     */
    public function testGetSupplierPool()
    {
        $this->assertEquals([], $this->testable->getSupplierPool);
    }
}
