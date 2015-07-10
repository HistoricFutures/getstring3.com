<?php

/**
 * @file
 * Abstract object which has an ID related to a DB row.
 *
 * Ideally we would use an ORM, but we want to be very lightweight.
 */

namespace TestRig\Models;

use TestRig\Services\Database;

/**
 * @class
 * AbstractDBObject.
 */
class AbstractDBObject
{
    // ID of current object.
    private $id = NULL;

    // Database table we save to.
    protected $table = NULL;
    // Path to database.
    protected $path = NULL;

    // Data for database.
    public $data = array();

    /**
     * Implements ::__construct().
     */
    public function __construct($path, $id = NULL, $arguments = array())
    {
        $this->path = $path;

        // If we have an ID, try to load the object.
        if ($id)
        {
            $this->read($id);
            return;
        }
        // Otherwise, create!
        $this->create($arguments);
    }

    /**
     * Create and save new object.
     *
     * Extended classes need to set $this->data.
     */
    public function create()
    {
        // Write data, and extract the ID into a private attribute.
        Database::writeRecord($this->path, $this->table, $this->data);
        $this->id = $this->data['id'];
    }

    /**
     * Read an existing database object into this PHP based on ID.
     */
    public function read($id)
    {
        $this->id = $id;
        $this->data = Database::readRecord($this->path, $this->table, $this->id);
    }

    /**
     * Update this object.
     */
    public function update()
    {
        // ID is not mutable.
        unset($this->data['id']);
        Database::updateRecord($this->path, $this->table, $this->id, $this->data);
        $this->data['id'] = $this->id;
    }

    /**
     * Delete this object.
     */
    public function delete()
    {
        Database::deleteRecord($this->path, $this->table, $this->id);
        $this->id = NULL;
        $this->data = array();
    }

    /**
     * Get ID of this object.
     */
    public function getID()
    {
        return $this->id;
    }
}
