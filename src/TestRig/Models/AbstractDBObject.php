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
    private $id = null;

    // Database table we save to.
    protected $table = null;
    // Path to database.
    protected $path = null;

    // Data for database.
    public $data = array();

    /**
     * Implements ::__construct().
     */
    public function __construct($path, $id = null, $arguments = array())
    {
        $this->path = $path;

        // If we have an ID, try to load the object.
        if ($id) {
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
        // ID is not settable.
        unset($this->data['id']);
        // Write data, and extract the ID into a private attribute.
        Database::writeRecord($this->path, $this->table, $this->data);
        $this->id = $this->data['id'];
    }

    /**
     * Read an existing database object into this PHP based on ID.
     */
    public function read($id)
    {
        // Unbind from existing row.
        $this->id = null;

        // Try to bind to a new row.
        $this->data = Database::readRecord($this->path, $this->table, $id);

        // Only set ID internally if we've been able to bind to a record.
        // This is of especial concern when we need to get records from
        // other tables following binding.
        if ($this->data) {
            $this->id = $id;
        }
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
        $this->id = null;
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
