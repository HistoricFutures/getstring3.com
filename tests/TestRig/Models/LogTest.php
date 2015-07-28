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
        $this->model->logInteraction("C", "D", 20, 15);

        // Now check they're logged properly, with incrementing time.
        $log = $this->model->getLog();
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
        $this->assertEquals(0, $this->model->timePasses(0));
        $this->assertEquals(10, $this->model->timePasses(10));
        $this->assertEquals(20, $this->model->timePasses(10));
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
    /**
     * Test:: TestRig\Models\Log::timeTravelTo().
     */
    public function testTimeTravelTo()
    {
        $this->model->timeTravelTo(50);
        $this->assertEquals(50, $this->model->timePasses());
        $this->model->logInteraction(1, 2);
        $this->model->timeTravelTo(25);
        $this->assertEquals(25, $this->model->timePasses());
        $this->model->logInteraction(1, 2);

        // Ensure logs in the "right" wrong order too!
        $log = $this->model->getLog();
        $this->assertEquals(50, $log[0]['start']);
        $this->assertEquals(25, $log[1]['start']);
    }
}
