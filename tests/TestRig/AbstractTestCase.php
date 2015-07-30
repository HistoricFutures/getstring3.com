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
    // Create a containing directory before object instantiated?
    protected static $containingDirEnvVar = null;
    protected static $containingDir = null;

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
     * Set up before class.
     */
    public static function setUpBeforeClass()
    {
        if (static::$containingDirEnvVar) {
            static::$containingDir = getenv(static::$containingDirEnvVar);
            if (static::$containingDir) {
                mkdir(static::$containingDir);
            }
        }
    }

    /**
     * Tear down after class: delete root folder.
     */
    public static function tearDownAfterClass()
    {
        if (static::$containingDir) {
            exec("rm -rf " . static::$containingDir);
        }
    }

    /**
     * Set up.
     */
    public function setUp()
    {
        // Create path to database.
        if ($this->pathToDatabase) {
            // Put it on the list of files to unlink when we tear down.
            $this->registerForUnlinking($this->pathToDatabase);
            $this->conn = Database::create($this->pathToDatabase);
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

    /**
     * Register a file for unlinking (and make sure it doesn't exist).
     */
    protected function registerForUnlinking($toUnlink)
    {
        $this->toUnlink[] = $toUnlink;
        if (file_exists($toUnlink)) {
            unlink($toUnlink);
        }
    }
}
