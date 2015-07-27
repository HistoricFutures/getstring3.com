<?php

/**
 * @file
 * An askable agent, layered on top of entities.
 */

namespace TestRig\Models;

use TestRig\Services\Database;
use TestRig\Services\Generate;
use TestRig\Services\Maths;

/**
 * @class
 * Agent.
 */
class Agent extends Entity
{
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
        $randomID = $conn->querySingle("SELECT id FROM entity WHERE 1 = 1 AND $filters ORDER BY RANDOM() LIMIT 1;");
        // Now we're putting filters in, we could end up with no suitable candidate.
        if (!$randomID) {
            return null;
        }
        return new Agent($path, $randomID);
    }

    /**
     * Pick another valid agent to ask the question.
     *
     * Questions can be commenced by calling this directly on an agent.
     *
     * @param Log $log
     *   Log for the entire question chain. This could include filters e.g.
     *   all asked IDs so far, so we don't re-ask the same agent.
     * @return mixed
     *   If a to-ask is found, return Agent object; otherwise null.
     */
    public function pickToAsk(Log $log)
    {
        return Agent::pickRandom($this->path, "tier = " . ($this->data['tier'] + 1));
    }

    /**
     * Respond either with an ack-and-pass-on or answer.
     */
    public function respondTo(Agent $from, Log $log)
    {
        $probability = $this->data['probability_no_ack'];

        // Now we have tiers, we have to check if we're the top tier or not.
        // If we don't get an askee, we're top tier!
        $toAsk = $this->pickToAsk($log);

        // Melters don't acknowledge or re-route; just respond NULL.
        if (Maths::evenlyRandomZeroOne() <= $probability) {
            $log->logInteraction(
                $from->getID(),
                $this->getID()
            );
        }
        // Final tier have no $toAsk candidate: ack and answer.
        elseif ($toAsk === null) {
            $log->logInteraction(
                $from->getID(),
                $this->getID(),
                Generate::getTime($this->data['mean_ack_time']),
                Generate::getTime($this->data['mean_answer_time'])
            );
        }
        // Otherwise ack, wait routing time, then route to to-ask in turn.
        else {
            // We might have to ask more than one supplier.
            $toAsks = array($toAsk);
            if ($this->data['mean_extra_suppliers']) {
                // Work out how many for this actual chain.
                $numSuppliers = Generate::getNumber(
                    $this->data['mean_extra_suppliers'],
                    $this->data['mean_extra_suppliers'] * 4
                );
                for ($i = 1; $i <= $numSuppliers; $i++) {
                    $toAsks[] = $this->pickToAsk($log);
                }
            }

            // Rather than generate times, travel back in time, each time.
            // Otherwise we'd have to take into account routing times etc.
            $tZero = $log->timePasses();
            foreach ($toAsks as $toAsk) {
                // Rewind time to T-zero, then kick off bifurcated route.
                $log->timeTravelTo($tZero);

                $log->logInteraction(
                    $from->getID(),
                    $this->getID(),
                    Generate::getTime($this->data['mean_ack_time'])
                );
                $log->timePasses(
                    Generate::getTime($this->data['mean_routing_time'])
                );

                $toAsk->respondTo($this, $log);
            }
        }
    }
}
