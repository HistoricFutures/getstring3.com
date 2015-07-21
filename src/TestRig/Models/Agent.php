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
     * Starts a chain by forcing an ask.
     *
     * @return array
     *   Log messages including timestamps etc.
     */
    public function go(Log $log)
    {
        $this->maybeAsk($log, 1);
    }

    /**
     * Maybe ask another agent, with logging.
     *
     * @param Log $log
     *   Log object for this agent to put actions into.
     * @param float $overrideProbability
     *   Probability of asking to use, instead of the agent's own. Useful
     *   for forcing the initial ask at the start of a chain.
     */
    public function maybeAsk(Log $log, $overrideProbability = null)
    {
        // Decide what the ask probability is, but distinguish betweeen an
        // override probability of zero (don't ask) and NULL (not defined).
        $probability = $this->data['probability_reask'];
        if ($overrideProbability !== null) {
            $probability = $overrideProbability;
        }

        if (Maths::evenlyRandomZeroOne() <= $probability) {
            $toAsk = Agent::pickRandom($this->path);
            $toAsk->maybeRespond($this, $log);
        }
    }

    /**
     * Maybe respond to an ask; maybe even re-ask to do so.
     *
     * @param Agent $asker
     *   Agent asking the question.
     * @param Log $log
     *   Log object for subsequent agents to also use.
     */
    public function maybeRespond(Agent $asker, Log $log)
    {
        // For now, always respond.
        // See if we need to re-ask first.
        $this->maybeAsk($log);
        $log->logInteraction(
            $asker->getID(),
            $this->getID(),
            Generate::getTime($this->data['mean_response_time'])
        );
    }
}
