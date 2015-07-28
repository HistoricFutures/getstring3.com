<?php

/**
 * @file
 * Abstract test case.
 */

namespace Tests;

use TestRig\Services\Database;

/**
 * @class
 * AbstractTestCase.
 */
abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    // Create and tear down database for each test.
    protected $pathToDatabase = null;
    // Database connection.
    protected $conn = null;

    // Do we create a testable object? Needs fully namespaced class.
    protected $testableClass = null;
    // And does it take the database path as __construct() argument?
    protected $testableClassNeedsDatabase = false;

    // Files/folders to unlink in tearDown.
    protected $toUnlink = array();

    /**
     * Set up.
     */
    public function setUp()
    {
        // Create path to database.
        if ($this->pathToDatabase) {
            $this->conn = Database::create($this->pathToDatabase);
            // Put it on the list of files to unlink when we tear down.
            $this->toUnlink[] = $this->pathToDatabase;
        }

        // Create a testable to test?
        if ($this->testableClass) {
            // Some testables need to access the database.
            if ($this->testableClassNeedsDatabase) {
                $this->testable = new $this->testableClass($this->pathToDatabase);
            } else {
                $this->testable = new $this->testableClass();
            }
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach ($this->toUnlink as $toUnlink) {
            unlink($toUnlink);
        }
    }
}
