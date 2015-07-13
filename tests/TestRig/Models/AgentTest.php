<?php

/**
 * @file
 * Test: TestRig\Models\Agent.
 */

use TestRig\Models\Agent;
use TestRig\Models\Log;
use TestRig\Services\Database;

/**
 * @class
 * Test: TestRig\Models\Agent.
 */
class AgentTest extends \PHPUnit_Framework_TestCase
{
    // Create and tear down database for each test.
    private $pathToDatabase = "/tmp/for-agent.sqlite3";
    // Database connection.
    private $conn = null;
    // Log object to pass into agent go calls.
    private $log = null;

    /**
     * Set up.
     */
    public function setUp()
    {
        $this->conn = Database::create($this->pathToDatabase);
        $this->agent = new Agent($this->pathToDatabase);
        $this->log = new Log();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        unlink($this->pathToDatabase);
    }

    /**
     * Test: TestRig\Models\Agent::pickRandom().
     */
    public function testPickRandom()
    {
        // Have 10 agents in total.
        for ($i = 0; $i < 10; $i++) {
            new Agent($this->pathToDatabase);
        }

        // Pick an agent at random ten times and store their IDs.
        for ($i = 0; $i <= 10; $i++) {
            $randomAgent = Agent::pickRandom($this->pathToDatabase);
            $ids[$randomAgent->getID()] = true;
        }
        // Assert we've got more than one agent at random, not always the same.
        $this->assertGreaterThan(1, count($ids));
    }

    /**
     * Test: TestRig\Models\Agent::go().
     */
    public function testGo()
    {
        $this->agent->go($this->log);
        $log = $this->log->getLog();

        // Have at least one entry: our first ask is mandated.
        $this->assertGreaterThan(0, count($log));
    }

    /**
     * Test: TestRig\Models\Agent::maybeAsk().
     */
    public function testMaybeAsk()
    {
        $this->agent->maybeAsk($this->log, 0);
        $this->assertEquals(0, count($this->log->getLog()));
        $this->agent->maybeAsk($this->log, 1);
        $this->assertGreaterThan(0, count($this->log->getLog()));
    }

    /**
     * Test: TestRig\Models\Agent::maybeRespond().
     */
    public function testMaybeRespond()
    {
        $toAsk = new Agent($this->pathToDatabase);
        $toAsk->maybeRespond($this->agent, $this->log);
        $this->assertGreaterThanOrEqual(1, count($this->log));

        // Last log item should be our asker and toAsk.
        $last = array_pop($this->log->getLog());
        $this->assertEquals($last['from'], $this->agent->getID());
        $this->assertEquals($last['to'], $toAsk->getID());
    }
}
