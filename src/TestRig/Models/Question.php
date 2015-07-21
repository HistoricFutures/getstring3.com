<?php

/**
 * @file
 * A single chain of interactions.
 */

namespace TestRig\Models;

use TestRig\Services\Database;

/**
 * @class
 * A single chain of interactions.
 */
class Question extends AbstractDBObject
{
    // Actions.
    private $actions = array();

    // Database table we save to.
    protected $table = 'question';

    /**
     * @inheritDoc
     */
    public function create()
    {
        // Reset actions array prior to create.
        $this->actions = array();
        // Let parent handle the rest.
        parent::create();
    }

    /**
     * @inheritDoc
     */
    public function read($id)
    {
        // Reset actions array prior to create.
        $this->actions = array();

        // Let parent handle the core entity.
        parent::read($id);

        // If the question doesn't exist, just quit silently here.
        if (!$this->getID()) {
            return;
        }

        // Otherwise, load its associated actions.
        $this->actions = Database::getRowsWhere(
            $this->path,
            'action',
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
        // A question can't be updated: all actions handled separately.
        throw new \Exception('Question cannot be updated.');
    }

    /**
     * Add an action to this question.
     *
     * @param array &$data
     *   Action data, passed by reference, using column names as per table.
     */
    public function addAction(&$data)
    {
        $data['question'] = $this->getID();
        Database::writeRecord($this->path, 'action', $data);
        $this->actions[] = $data;
    }

    /**
     * Retrieve all actions for this question.
     *
     * @return array
     *   All actions associated with the question chain.
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Generate a chain of actions for this question.
     */
    public function generateActions()
    {
        // Generate a log from the agent(s).
        $log = new Log();
        $initiator = Agent::pickRandom($this->path);
        $initiator->go($log);

        // Convert the log into the actions format.
        foreach ($log->getLog() as $logItem) {
            $action = array(
                'entity_from' => $logItem['from'],
                'entity_to' => $logItem['to'],
                'time_start' => $logItem['start'],
                'time_stop' => $logItem['end'],
            );
            $this->addAction($action);
        }
    }
}
