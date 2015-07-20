<?php

/**
 * @file
 * Test: TestRig\Models\Executor.
 */

use Symfony\Component\Yaml\Dumper;
use TestRig\Models\Dataset;
use TestRig\Models\Executor;
use TestRig\Services\Filesystem;

/**
 * @class
 * Test: TestRig\Models\Executor.
 */
class ExecutorTest extends \PHPUnit_Framework_TestCase
{
    // To be replaced with new Executor() during setUpBeforeClass.
    public static $model = null;
    // Root directory for folders: we keep track too.
    private static $rootDir = null;

    /**
     * Set up before class: create root folder and Executor() handler class.
     */
    public static function setUpBeforeClass()
    {
        self::$rootDir = getenv('DIR_ALGORITHMS');
        mkdir(self::$rootDir);
        self::$model = new Executor();
    }

    /**
     * Tear down after class: delete root folder.
     */
    public static function tearDownAfterClass()
    {
        Filesystem::removeDirectory(self::$rootDir);
    }

    public function testFail()
    {
        $this->fail(self::$model->run("test"));
    }
}
