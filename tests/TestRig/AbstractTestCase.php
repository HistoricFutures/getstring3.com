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
        }
        $this->toUnlink[] = $this->pathToDatabase;
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
