<?php

/**
 * @file
 * An askable agent, layered on top of entities.
 */

namespace TestRig\Models;

use TestRig\Exceptions\TierIntegrityException;
use TestRig\Services\Database;
use TestRig\Services\Generate;
use TestRig\Services\Maths;

/**
 * @class
 * Agent.
 */
class Agent extends Entity
{
    // Vertical (multi-tiered) agents need a concept of tier context.
    private $tierContext = null;

    /**
     * Pick a random agent and return: class method.
     *
     * In future we might support filters e.g. only a particular tier,
     * but we do need to be able to drop back to no filters so we can
     * still parse old databases.
     *
     * @param string $path
     *   Path to SQLite file.
     * @param string $filters
     *   WHERE fragment to go after "WHERE ... AND "
     * @return Agent
     */
    public static function pickRandom($path, $filters = null)
    {
        $conn = Database::getConn($path);
        if ($filters === null) {
            $filters = "1 = 1";
        }

        // Getting a random row from a SQLite table is a hack!
        $randomID = $conn->querySingle("SELECT e.id FROM entity e INNER JOIN entity_tier et ON et.entity = e.id WHERE 1 = 1 AND $filters ORDER BY RANDOM() LIMIT 1;");

        // Now we're putting filters in, we could end up with no suitable candidate.
        if (!$randomID) {
            return null;
        }
        return new Agent($path, $randomID);
    }

    /**
     * Pick a random agent that's also a valid to-ask: class method.
     *
     * @param string $path
     *   Path to SQLite file.
     * @param integer $tier
     *   Tier to pick from.
     * @param array $pool = [].
     *   Pool to pick from first, then try rest of population.
     * @return Agent
     */
    public static function pickRandomButValid($path, $tier, $pool = [])
    {
        // Simple tier requirement to start off.
        $where = "tier = $tier";

        // If we've got a pool of IDs, try to pick from them first.
        if ($pool) {
            $wherePool = implode(',', $pool);
            $agent = Agent::pickRandom($path, "$where AND id IN ($wherePool)");
            var_dump($agent);
            if ($agent) {
                return $agent;
            }
        }

        // If nothing suitable in the pool, just pick based on other criteria.
        return Agent::pickRandom($path, $where);
    }

    /**
     * Pick valid agents to ask the question, including potentially many suppliers.
     *
     * Questions can be commenced by calling this directly on an agent.
     *
     * @param Log $log
     *   Log for the entire question chain. This could include filters e.g.
     *   all asked IDs so far, so we don't re-ask the same agent.
     * @return array
     *   If to-asks are found, return an array of those Agents.
     */
    public function pickToAsks(Log $log)
    {
        // Try to get one to-ask; if we can't even get one, return empty array.
        // Sourcing agents get to-asks from the same tier as them.
        $toAskTier = $this->getTierContext() + ($this->data['is_sourcing'] ? 0 : 1);
        $toAsks = [Agent::pickRandomButValid($this->path, $toAskTier, $this->data['supplier_pool'])];
        if (!$toAsks[0]) {
            return array();
        }

        // We might have to ask more than one supplier.
        if ($this->data['mean_extra_suppliers']) {
            // Work out how many for this actual chain.
            $numSuppliers = Generate::getNumber(
                $this->data['mean_extra_suppliers'],
                $this->data['mean_extra_suppliers'] * 4
            );
            for ($i = 1; $i <= $numSuppliers; $i++) {
                $toAsks[] = Agent::pickRandomButValid($this->path, $toAskTier, $this->data['supplier_pool']);
            }
        }

        // Set tier context on all of our to-asks.
        foreach ($toAsks as $toAsk) {
            $toAsk->setTierContext($toAskTier);
        }

        return $toAsks;
    }

    /**
     * Respond either with an ack-and-pass-on or answer.
     */
    public function respondTo(Agent $from, Log $log)
    {
        $noAck = $this->data['probability_no_ack'];
        $noAnswer = $this->data['probability_no_answer'];

        // Now we have tiers, we have to check if we're the top tier or not.
        // If we don't get an askee, we're top tier!
        $toAsks = $this->pickToAsks($log);

        // Calculate a time multiplier now, based on the agent is responding
        // to itself or not.
        $askingSelf = ($from->getID() === $this->getID());
        $timeRatio = $askingSelf ? $this->data['self_time_ratio'] : 1;

        // Melters don't acknowledge or re-route; just respond NULL.
        if (Maths::evenlyRandomZeroOne() <= $noAck) {
            $log->logInteraction(
                $from->getID(),
                $this->getID()
            );
        }

        // Final tier have no $toAsk candidate: ack and (if suitable) answer.
        elseif (!$toAsks) {
            if (Maths::evenlyRandomZeroOne() <= $noAnswer) {
                $log->logInteraction(
                    $from->getID(),
                    $this->getID(),
                    Generate::getTime($this->data['mean_ack_time']) * $timeRatio
                );
            } else {
                $log->logInteraction(
                    $from->getID(),
                    $this->getID(),
                    Generate::getTime($this->data['mean_ack_time']) * $timeRatio,
                    Generate::getTime($this->data['mean_answer_time']) * $timeRatio
                );
            }
        }
        // Otherwise ack, wait routing time, then route to to-ask in turn.
        else {
            // Log this agent's acknowledgement of the ask just once.
            $log->logInteraction(
                $from->getID(),
                $this->getID(),
                Generate::getTime($this->data['mean_ack_time']) * $timeRatio
            );

            // Rather than generate times, we travel back in time, each time.
            // Otherwise we'd have to take into account routing times etc.
            $tZero = $log->timePasses();

            // Now, for each to-ask, rewind time to T-zero, then kick off
            // bifurcated route.
            foreach ($toAsks as $toAsk) {
                $log->timeTravelTo($tZero);

                // We want a different time ratio for routing, because we're
                // no longer considering the ask $from=>$this, but rather
                // the new ask $this=>$toAsk.
                $routingToSelf = ($this->getID() === $toAsk->getID());
                $routingTimeRatio = $routingToSelf ? $this->data['self_time_ratio'] : 1;
                $log->timePasses(
                    Generate::getTime($this->data['mean_routing_time']) * $routingTimeRatio
                );

                $toAsk->respondTo($this, $log, true);
            }
        }
    }

    /**
     * Set the tier context that this agent is operating in.
     *
     * Vertical agents can be in more than one tier, but within the
     * context of a particular ask, they will be being asked in their
     * role as an agent of tier N. So we need an idea of tier context.
     *
     * @param int $tier
     *   Tier context. Must be a valid tier.
     * @throws TierIntegrityException
     */
    public function setTierContext($tier)
    {
        if (!in_array($tier, $this->data['tiers'])) {
            throw new TierIntegrityException("Tried to set invalid tier context '$tier'.");
        }

        $this->tierContext = $tier;
    }

    /**
     * Get the tier context that this agent is operating in.
     *
     * See #setTierContext() for more information.
     *
     * @return int
     *   Current tier context or sets as lowest tier if not yet set.
     */
    public function getTierContext()
    {
        if ($this->tierContext === null) {
            $this->setTierContext(min($this->data['tiers']));
        }

        return $this->tierContext;
    }
}
