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

        // Have 10 agents in total.
        for ($i = 1; $i < 10; $i++) {
            new Agent($this->pathToDatabase);
        }
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
        // Pick an agent at random ten times and store their IDs.
        for ($i = 0; $i <= 10; $i++) {
            $randomAgent = Agent::pickRandom($this->pathToDatabase);
            $ids[$randomAgent->getID()] = true;
        }
        // Assert we've got more than one agent at random, not always the same.
        $this->assertGreaterThan(1, count($ids));
    }

    /**
     * Test: TestRig\Models\Agent::pickAndAsk().
     */
    public function testPickAndAsk()
    {
        $this->agent->pickAndAsk($this->log);
        $logSoFar = $this->log->getLog();

        // Second log item should always be tied to the first by agent ID.
        if (count($logSoFar) > 1) {
            $this->assertEquals($logSoFar[0]['to'], $logSoFar[1]['from']);
        }
        // Log will NEVER be empty: always at least one ask.
        else {
            $this->assertNotEmpty($logSoFar);
        }
    }

    /**
     * Test: TestRig\Models\Agent::respondTo().
     */
    public function testRespondTo()
    {
        $toAsk = new Agent($this->pathToDatabase);
        $toAsk->respondTo($this->agent, $this->log);
        $this->assertGreaterThanOrEqual(1, count($this->log));

        // First log item should be our asker and toAsk.
        $first = array_shift($this->log->getLog());
        $this->assertEquals($first['from'], $this->agent->getID());
        $this->assertEquals($first['to'], $toAsk->getID());

        // Re-ask with an agent with zero chance of re-routing.
        $toAsk->data['probability_answer'] = 1;
        $newLog = new Log();
        $toAsk->respondTo($this->agent, $newLog);
        $logItems = $newLog->getLog();
        $this->assertEquals(1, count($logItems));
        $lastItem = array_pop($logItems);
        $this->assertArrayHasKey('answer', $lastItem);

        // Re-ask with an agent with 1 chance of re-routing.
        $toAsk->data['probability_answer'] = 0;
        $newLog = new Log();
        $toAsk->respondTo($this->agent, $newLog);
        $logItems = $newLog->getLog();
        $this->assertGreaterThan(1, count($logItems));
        $lastItem = array_pop($logItems);
        $this->assertArrayHasKey('answer', $lastItem);
    }
}
