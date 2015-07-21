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
     * @param array $filters
     *   Not yet used.
     * @return Agent
     */
    public static function pickRandom($path, $filters = array())
    {
        $conn = Database::getConn($path);
        // Getting a random row from a SQLite table is a hack!
        $randomID = $conn->querySingle("SELECT id FROM entity WHERE _ROWID_ >= (abs(random()) % (SELECT max(_ROWID_) FROM entity)) LIMIT 1;");
        return new Agent($path, $randomID);
    }

    /**
     * Pick another agent and ask them the question.
     *
     * Questions can be commenced by calling this directly on an agent.
     *
     * @param Log $log
     *   Log for the entire question chain.
     */
    public function pickAndAsk(Log $log)
    {
        $toAsk = Agent::pickRandom($this->path);
        $toAsk->respondTo($this, $log);
    }

    /**
     * Respond either with an ack-and-pass-on or answer.
     */
    public function respondTo(Agent $from, Log $log)
    {
        $probability = $this->data['probability_answer'];

        // Answer directly and terminate chain....
        if (Maths::evenlyRandomZeroOne() <= $probability) {
            $log->logInteraction(
                $from->getID(),
                $this->getID(),
                Generate::getTime($this->data['mean_ack_time']),
                Generate::getTime($this->data['mean_answer_time'])
            );
        }
        // ... Or acknowledge, wait to route, then pick a new agent to ask.
        else {
            $log->logInteraction(
                $from->getID(),
                $this->getID(),
                Generate::getTime($this->data['mean_ack_time'])
            );
            $log->timePasses(
                Generate::getTime($this->data['mean_routing_time'])
            );

            $this->pickAndAsk($log);
        }
    }
}
