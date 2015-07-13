<?php

/**
 * @file
 * A single ask chain of questions.
 */

namespace TestRig\Models;

use TestRig\Services\Database;

/**
 * @class
 * A single ask chain of questions.
 */
class Ask extends AbstractDBObject
{
    // Actions.
    private $actions = array();

    // Database table we save to.
    protected $table = 'ask';

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

        // If the ask doesn't exist, just quit silently here.
        if (!$this->getID())
        {
            return;
        }

        // Otherwise, load its associated actions.
        $this->actions = Database::getRowsWhere(
            $this->path,
            'action',
            array('ask' => $this->getID())
        );       
    }

    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function update()
    {
        // An ask can't be updated: all actions handled separately.
        throw new \Exception('Ask cannot be updated.');
    }

    /**
     * Add an action to this ask.
     *
     * @param array &$data
     *   Action data, passed by reference, using column names as per table.
     */
    public function addAction(&$data)
    {
        Database::writeRecord($this->path, 'action', $data);
        $this->actions[] = $data;
    }

    /**
     * Retrieve all actions for this ask.
     *
     * @return array
     *   All actions associated with the task.
     */
    public function getActions()
    {
        return $this->actions;
    }
}
