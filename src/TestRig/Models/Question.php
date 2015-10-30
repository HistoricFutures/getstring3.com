<?php

/**
 * @file
 * A single chain of asks.
 */

namespace TestRig\Models;

use TestRig\Services\Database;

/**
 * @class
 * A single chain of asks.
 */
class Question extends AbstractDBObject
{
    // Asks.
    private $asks = array();

    // Database table we save to.
    protected $table = 'question';

    /**
     * @inheritDoc
     */
    public function create()
    {
        // Reset asks array prior to create.
        $this->asks = array();
        // Let parent handle the rest.
        parent::create();
    }

    /**
     * @inheritDoc
     */
    public function read($id)
    {
        // Reset asks array prior to create.
        $this->asks = array();

        // Let parent handle the core entity.
        parent::read($id);

        // If the question doesn't exist, just quit silently here.
        if (!$this->getID()) {
            return;
        }

        // Otherwise, load its associated asks.
        $this->asks = Database::getRowsWhere(
            $this->path,
            'ask',
            array('question' => $this->getID())
        );
    }

    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function update()
    {
        // A question can't be updated: all asks handled separately.
        throw new \Exception('Question cannot be updated.');
    }

    /**
     * Add an asks to this question.
     *
     * @param array &$data
     *   Ask data, passed by reference, using column names as per table.
     */
    public function addAsk(&$data)
    {
        $data['question'] = $this->getID();
        Database::writeRecord($this->path, 'ask', $data);
        $this->asks[] = $data;
    }

    /**
     * Retrieve all asks for this question.
     *
     * @return array
     *   All asks associated with the question chain.
     */
    public function getAsks()
    {
        return $this->asks;
    }

    /**
     * Generate a chain of asks for this question.
     */
    public function generateAsks()
    {
        print "Ask\n";
        // Generate a log from the agent(s).
        $log = new Log();
        // Always start at tier=1.
        $initiator = Agent::pickRandoms($this->path, "tier = 1");
        $initiator = $initiator[0];
        // Get valid to-asks, keep asking each one, and rewind time as
        // per in Agent::respondTo().
        $tZero = $log->timePasses();
        foreach ($initiator->pickToAsks($log) as $toAsk) {
            $log->timeTravelTo($tZero);
            $toAsk->respondTo($initiator, $log);
        }

        // Convert the log into the ask format.
        foreach ($log->getLog() as $logItem) {
            $ask = array(
                'entity_from' => $logItem['from'],
                'entity_to' => $logItem['to'],
                'time_start' => $logItem['start'],
            );
            if (isset($logItem['ack'])) {
                $ask['time_ack'] = $logItem['ack'];
            }
            if (isset($logItem['answer'])) {
                $ask['time_answer'] = $logItem['answer'];
            }
            // This could be done in bulk?
            $this->addAsk($ask);
        }
    }
}
