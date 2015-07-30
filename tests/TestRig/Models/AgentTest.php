<?php

/**
 * @file
 * Test: TestRig\Models\Agent.
 */

namespace Tests\Models;

use TestRig\Models\Agent;
use TestRig\Models\Log;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Models\Agent.
 */
class AgentTest extends AbstractTestCase
{
    // Create and tear down database for each test.
    protected $pathToDatabase = "/tmp/for-agent.sqlite3";
    // Log object to pass into agent go calls.
    private $log = null;

    // Do we create a testable model?
    protected $testableClass = 'TestRig\Models\Agent';
    // And does it take the database path as __construct() argument?
    protected $testableClassNeedsDatabase = true;

    /**
     * Set up.
     */
    public function setUp()
    {
        parent::setUp();
        // Have 10 agents in total: we can't test behaviours with just one.
        for ($i = 1; $i < 10; $i++) {
            new Agent($this->pathToDatabase);
        }
        // Logs are how we reconstruct agent behaviour history.
        $this->log = new Log();
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
     * Test: TestRig\Models\Agent::pickToAsk().
     */
    public function testPickToAsk()
    {
        // With all agents in default tier=1, nobody to ask.
        $toAsks = $this->testable->pickToAsks($this->log);
        $this->assertEmpty($toAsks);
        // Add a tier=2 agent: this will be our only to-ask candidate!
        $tier2Agent = new Agent($this->pathToDatabase, null, array("tier" => 2));
        $toAsks = $this->testable->pickToAsks($this->log);
        $this->assertEquals($tier2Agent->getID(), $toAsks[0]->getID());

        $toAsks[0]->respondTo($this->testable, $this->log);

        $logSoFar = $this->log->getLog();

        // Second log item should always be tied to the first by agent ID.
        if (count($logSoFar) > 1) {
            $this->assertEquals($logSoFar[0]['to'], $logSoFar[1]['from']);
        }
        // Log will NEVER be empty: always at least one ask.
        else {
            $this->assertNotEmpty($logSoFar);
        }

        // Increase our number of suppliers and expect
        // more agents to come out of pickToAsk() (even if repeat for now.)
        $this->testable->data['mean_extra_suppliers'] = 20;
        $this->assertGreaterThan(5, count($this->testable->pickToAsks($this->log)));

        // Make this agent a sourcing agent and ensure it gets data from same tier.
        $this->testable->data['mean_extra_suppliers'] = 0;
        $this->testable->data['is_sourcing'] = true;
        $source = $this->testable->pickToAsks($this->log);
        $this->assertEquals($this->testable->data['tier'], $source[0]->data['tier']);
    }

    /**
     * Test: TestRig\Models\Agent::respondTo().
     */
    public function testRespondTo()
    {
        // Since we added tiers, we need a tier 2 agent for routing to happen.
        $tier2Agent = new Agent($this->pathToDatabase, null, array("tier" => 2));

        $toAsk = new Agent($this->pathToDatabase);
        $toAsk->respondTo($this->testable, $this->log);
        $this->assertGreaterThanOrEqual(1, count($this->log));

        // First log item should be our asker and toAsk.
        $first = array_shift($this->log->getLog());
        $this->assertEquals($first['from'], $this->testable->getID());
        $this->assertEquals($first['to'], $toAsk->getID());

        // Re-ask with an agent with zero chance of acknowledging.
        $toAsk->data['probability_no_ack'] = 1;
        $newLog = new Log();
        $toAsk->respondTo($this->testable, $newLog);
        $logItems = $newLog->getLog();
        $this->assertEquals(1, count($logItems));
        $this->assertArrayNotHasKey('ack', $logItems[0]);
        $this->assertArrayNotHasKey('ack', $logItems[0]);

        // Re-ask with an agent with 1 chance of acknowledging (and rerouting).
        $toAsk->data['probability_no_ack'] = 0;
        $newLog = new Log();
        $toAsk->respondTo($this->testable, $newLog);
        $logItems = $newLog->getLog();
        $this->assertGreaterThan(1, count($logItems));
        $lastItem = array_pop($logItems);
        $this->assertArrayHasKey('answer', $lastItem);

        // Re-ask with extra suppliers. Run ten times and average number
        // of suppliers toAsk picks must be greater than 10.
        $toAsk->data['mean_extra_suppliers'] = 2;

        $countSuppliers = 0;
        for ($i = 1; $i <= 10; $i++) {
            $newLog = new Log();
            $toAsk->respondTo($this->testable, $newLog);
            $logItems = $newLog->getLog();

            // Count the ones from toAsk to other supplier(s).
            // Threading / time travel means they'll be in a funny order.
            foreach ($logItems as $logItem) {
                if ($logItem['from'] === $toAsk->getID()) {
                    $countSuppliers++;
                }
            }
        }
        $this->assertGreaterThan(10, $countSuppliers);
    }
}
