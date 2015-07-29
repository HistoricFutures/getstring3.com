<?php

/**
 * @file
 * Test: TestRig\Models\Log.
 */

namespace Tests\Models;

use TestRig\Models\Log;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Models\Log.
 */
class LogTest extends AbstractTestCase
{
    // Do we create a testable object? Needs fully namespaced class.
    protected $testableClass = 'TestRig\Models\Log';

    /**
     * Test:: TestRig\Models\Log::logInteraction().
     */
    public function testLogInteraction()
    {
        // Queue up a couple of log items.
        $this->testable->logInteraction("A", "B", 50);
        $this->testable->logInteraction("C", "D", 20, 15);

        // Now check they're logged properly, with incrementing time.
        $log = $this->testable->getLog();
        $this->assertEquals("A", $log[0]["from"]);
        $this->assertEquals("B", $log[0]["to"]);
        $this->assertEquals(0, $log[0]["start"]);
        $this->assertEquals(50, $log[0]["ack"]);
        $this->assertEquals("C", $log[1]["from"]);
        $this->assertEquals("D", $log[1]["to"]);
        $this->assertEquals(50, $log[1]["start"]);
        $this->assertEquals(70, $log[1]["ack"]);
        $this->assertEquals(85, $log[1]["answer"]);
    }

    /**
     * Test:: TestRig\Models\Log::timePasses().
     */
    public function testTimePasses()
    {
        $this->assertEquals(0, $this->testable->timePasses(0));
        $this->assertEquals(10, $this->testable->timePasses(10));
        $this->assertEquals(20, $this->testable->timePasses(10));
    }

    /**
     * Test:: TestRig\Models\Log::getLog().
     */
    public function testGetLog()
    {
        // Not much to do here; just check return value. We'll check
        // the structure in more depth during testLogInteraction().
        $this->assertTrue(is_array($this->testable->getLog()));
    }
    /**
     * Test:: TestRig\Models\Log::timeTravelTo().
     */
    public function testTimeTravelTo()
    {
        $this->testable->timeTravelTo(50);
        $this->assertEquals(50, $this->testable->timePasses());
        $this->testable->logInteraction(1, 2);
        $this->testable->timeTravelTo(25);
        $this->assertEquals(25, $this->testable->timePasses());
        $this->testable->logInteraction(1, 2);

        // Ensure logs in the "right" wrong order too!
        $log = $this->testable->getLog();
        $this->assertEquals(50, $log[0]['start']);
        $this->assertEquals(25, $log[1]['start']);
    }
}
