<?php

/**
 * @file
 * An entity agent.
 *
 * Ideally we would use an ORM, but we want to be very lightweight.
 */

namespace TestRig\Models;

use TestRig\Services\Database;
use TestRig\Services\Generate;

/**
 * @class
 * Entity.
 */
class Entity
{
    // Path to database.
    private $path = NULL;
    // ID of current entity.
    private $id = NULL;

    // Data for database.
    public $data = array();

    /**
     * Implements ::__construct().
     */
    public function __construct($path, $id = NULL, $arguments = array())
    {
        $this->path = $path;

        // If we have an ID, try to load the entity.
        if ($id)
        {
            $this->read($id);
            return;
        }
        // Otherwise, create!
        $this->create($arguments);
    }

    /**
     * Create and save new entity.
     *
     * @param array $arguments
     *   Any arguments to be saved alongside autogenerated name.
     */
    public function create($arguments = array())
    {
        // Create data suitable for database.
        $this->data = array();
        $this->data['name'] = isset($arguments['name']) ? $arguments['name']
            : Generate::getEntityName();

        // Write data, and extract the ID into a private attribute.
        Database::writeRecord($this->path, "entity", $this->data);
        $this->id = $this->data['id'];
    }

    /**
     * Read an existing entity into this object based on ID.
     */
    public function read($id)
    {
        $this->id = $id;
        $this->data = Database::readRecord($this->path, "entity", $this->id);
    }

    /**
     * Update this entity.
     */
    public function update()
    {
        // ID is not mutable.
        unset($this->data['id']);
        Database::updateRecord($this->path, "entity", $this->id, $this->data);
        $this->data['id'] = $this->id;
    }

    /**
     * Delete this entity.
     */
    public function delete()
    {
        Database::deleteRecord($this->path, "entity", $this->id);
        $this->id = NULL;
        $this->data = array();
    }
}
