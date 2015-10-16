<?php

/**
 * @file
 * Test: TestRig\Models\Agent.
 */

namespace Tests\Models;

use TestRig\Exceptions\TierIntegrityException;
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
     * Test: TestRig\Models\Agent::pickRandomButValid().
     */
    public function testPickRandomButValid()
    {
        // Pick an agent at random ten times and store their IDs.
        for ($i = 0; $i <= 10; $i++) {
            $randomAgent = Agent::pickRandomButValid($this->pathToDatabase, 1);
            $ids[$randomAgent->getID()] = true;
        }
        // Assert we've got more than one agent at random, not always the same.
        $this->assertGreaterThan(1, count($ids));

        // Add an entity to tier 2 and show we can then pick it.
        $this->assertNull(Agent::pickRandomButValid($this->pathToDatabase, 2));
        $agent = new Agent($this->pathToDatabase, null, ['tiers' => [2]]);
        $this->assertEquals(
            $agent->getID(),
            Agent::pickRandomButValid($this->pathToDatabase, 2)->getID()
        );
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
        $tier2Agent = new Agent($this->pathToDatabase, null, array("tiers" => [2]));
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
        $this->assertEquals($this->testable->data['tiers'][0], $source[0]->data['tiers'][0]);

        // Give this agent a pool.
        $this->testable->data['is_sourcing'] = false;
        $this->testable->data['mean_supplier_pool_size'] = 1;
        $this->testable->data['probability_pick_from_pool'] = 1;
        $this->testable->generateSupplierPool();
        // Add several tier=2 agents that won't be in this pool.
        for ($i = 0; $i < 5; $i++) {
            new Agent($this->pathToDatabase, null, array("tiers" => [2]));
        }
        // Ensure it always picks from its pool, if it can.
        $pool = $this->testable->getSupplierPool();
        $toAsks = $this->testable->pickToAsks($this->log);
        $this->assertEquals($pool[0], $toAsks[0]->getID());
        // Make it never pick from its pool.
        $this->testable->data['probability_pick_from_pool'] = 0;
        $pool = $this->testable->getSupplierPool();
        // It still might pick the same supplier by coincidence, so try picking
        // several times and confirm that there's at least one non-pool result.
        $nonPoolResults = [];
        for ($i = 0; $i < 5; $i++) {
            $toAsks = $this->testable->pickToAsks($this->log);
            $nonPoolResults[$toAsks[0]->getID()] = true;
            // Picking from outside the pool should add the new supplier to
            // the pool each time.
            $changedPool = $this->testable->getSupplierPool();
            $this->assertEquals($toAsks[0]->getID(), $changedPool[0]);
        }
        // Ignore the in-pool supplier and check we've still got results.
        unset($nonPoolResults[$pool[0]]);
        $this->assertGreaterThan(
            0, count($nonPoolResults),
            'Agent that never picks from its pool has only ever picked a pool supplier: not fatal, but suspicious!'
        );
    }

    /**
     * Test: TestRig\Models\Agent::respondTo().
     */
    public function testRespondTo()
    {
        // Since we added tiers, we need a tier 2 agent for routing to happen.
        $tier2Agent = new Agent($this->pathToDatabase, null, array("tiers" => [2]));

        // We're actually forcing a tier-1 agent to ask a tier-1 agent here.
        // That's OK, but toAsk will then ask tier 2 as it's not a sourcing agent.
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

        // Re-ask with an agent with 1 chance of acknowledging (and rerouting).
        $toAsk->data['probability_no_ack'] = 0;
        $lastItem = $this->respondToAndGetLogItems($toAsk);
        // We only have 1 tier-2 agent, so we have to end up there.
        $this->assertEquals($tier2Agent->getID(), $lastItem['to']);
        $this->assertArrayHasKey('ack', $lastItem);
        $this->assertArrayHasKey('answer', $lastItem);

        // Set our sole tier2Agent to never answer, and re-run the test.
        $tier2Agent->data['probability_no_answer'] = 1;
        $tier2Agent->update();
        $lastItem = $this->respondToAndGetLogItems($toAsk);
        // Our last item should no longer have an answer.
        $this->assertEquals($tier2Agent->getID(), $lastItem['to']);
        $this->assertArrayHasKey('ack', $lastItem);
        $this->assertArrayNotHasKey('answer', $lastItem);
        // Re-set tier2Agent back to always answering.
        $tier2Agent->data['probability_no_answer'] = 0;
        $tier2Agent->update();

        // Turn our tier2agent into a vertical agent
        $tier2Agent->data['tiers'] = [2, 3];
        $tier2Agent->update();
        // Now it should always reply to itself.
        $lastItem = $this->respondToAndGetLogItems($toAsk);
        $this->assertEquals($lastItem['from'], $lastItem['to']);
        foreach (array('ack', 'answer') as $time) {
            $this->assertNotEquals($lastItem['start'], $lastItem[$time], "Time $time was not equal to start");
        }
        // Set its internal routing times etc. to zero and re-run.
        $tier2Agent->data['self_time_ratio'] = 0;
        $tier2Agent->update();
        $lastItems = $this->respondToAndGetLogItems($toAsk, null, [1, 2]);
        // Ack and answer should be the same (zero difference from start).
        // Routing should be the same, but this is measured as time between
        // previous item's ack and this item's start.
        foreach (array('ack', 'answer') as $time) {
            $this->assertEquals($lastItems[2]['start'], $lastItems[2][$time], "Vertical agent's internal time $time was not equal to start: response took time!");
            $this->assertEquals($lastItems[1]['ack'], $lastItems[2][$time], "Vertical agent's internal time $time was not equal to previous log item's ack: routing took time!");
        }

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

    /**
     * Test: TestRig\Models\Agent::setTierContext().
     */
    public function testSetTierContext()
    {
        $verticalAgent = new Agent($this->pathToDatabase, null, array("tiers" => [1, 2, 3]));

        $verticalAgent->setTierContext(1);
        $this->assertEquals(1, $verticalAgent->getTierContext());
        $verticalAgent->setTierContext(3);
        $this->assertEquals(3, $verticalAgent->getTierContext());

        // Set some invalid tiers.
        foreach (array(5, "not a tier", "", 0) as $invalidTier) {
            try {
                $verticalAgent->setTierContext($invalidTier);
                $this->fail("Could set tier to be invalid '$invalidTier'.");
            } catch (TierIntegrityException $e) {
            }
        }
    }

    /**
     * Test: TestRig\Models\Agent::getTierContext().
     */
    public function testGetTierContext()
    {
        $verticalAgent = new Agent($this->pathToDatabase, null, array("tiers" => [1, 2, 3]));
        $log = new Log();

        $toAsks = $verticalAgent->pickToAsks($log);
        $toAsks2 = $toAsks[0]->pickToAsks($log);

        // All the same agent.
        $this->assertEquals($toAsks[0]->getID(), $verticalAgent->getID());
        $this->assertEquals($toAsks2[0]->getID(), $verticalAgent->getID());

        // But tier context should change.
        $this->assertEquals(1, $verticalAgent->getTierContext());
        $this->assertEquals(2, $toAsks[0]->getTierContext());
        $this->assertEquals(3, $toAsks2[0]->getTierContext());
    }

    /**
     * Private helper function: get last log item.
     *
     * Avoids a lot of boilerplate above.
     *
     * @param Agent $toAsk
     *   Agent to call ->respondTo($this->testable, ...) on.
     * @param Log $log = null
     *   Log object; creates a new one if not provided.
     * @param mixed $which = 'last'
     *   Which items to return; defaults to 'last'. Array of keys returns them.
     * @return array
     *   Last log entry via array_pop().
     */
    private function respondToAndGetLogItems(Agent $toAsk, Log $log = null, $which = 'last')
    {
        if ($log === null) {
            $log = new Log();
        }

        $toAsk->respondTo($this->testable, $log);
        $logItems = $log->getLog();

        // By default, return last item in log.
        if (!is_array($which)) {
            return array_pop($logItems);
        }

        // If an array specified, try to return those items.
        $toReturn = array();
        foreach ($which as $key) {
            $toReturn[$key] = $logItems[$key];
        }
        return $toReturn;
    }
}
