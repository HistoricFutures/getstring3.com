<?php

/**
 * @file
 * Test: TestRig\Models\Log.
 */

use TestRig\Models\Log;

/**
 * @class
 * Test: TestRig\Models\Log.
 */
class LogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set up
     */
    public function setUp()
    {
        $this->model = new Log();
    }

    /**
     * Test:: TestRig\Models\Log::logInteraction().
     */
    public function testLogInteraction()
    {
        // Queue up a couple of log items.
        $this->model->logInteraction("A", "B", 50);
        $this->model->logInteraction("C", "D", 20);

        // Now check they're logged properly, with incrementing time.
        $log = $this->model->getLog();
        $this->assertEquals("A", $log[0]["from"]);
        $this->assertEquals("B", $log[0]["to"]);
        $this->assertEquals(0, $log[0]["start"]);
        $this->assertEquals(50, $log[0]["end"]);
        $this->assertEquals("C", $log[1]["from"]);
        $this->assertEquals("D", $log[1]["to"]);
        $this->assertEquals(50, $log[1]["start"]);
        $this->assertEquals(70, $log[1]["end"]);
    }

    /**
     * Test:: TestRig\Models\Log::getLog().
     */
    public function testGetLog()
    {
        // Not much to do here; just check return value. We'll check
        // the structure in more depth during testLogInteraction().
        $this->assertTrue(is_array($this->model->getLog()));
    }
}
