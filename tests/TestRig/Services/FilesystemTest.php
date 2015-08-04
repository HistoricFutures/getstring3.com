<?php

/**
 * @file
 * Test: TestRig\Services\Filesystem.
 */

namespace Tests\Services;

use TestRig\Services\Filesystem;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Services\Filesystem.
 */
class FilesystemTest extends AbstractTestCase
{
    // Create a containing directory before object instantiated.
    protected static $containingDir = '/tmp/for-filesystem';

    /**
     * Test: TestRig\Services\Filesystem::removeDirectory().
     */
    public function testRemoveDirectory()
    {
        // Make up a test directory name and ensure it doesn't exist.
        $testDirectory = "/tmp/testrig-" . getmypid();
        $this->assertFalse(file_exists($testDirectory));

        // Create directory with subdirectories and files.
        mkdir($testDirectory);
        mkdir("$testDirectory/foo");
        touch("$testDirectory/bar.txt");
        touch("$testDirectory/foo/quux.txt");
        $this->assertTrue(file_exists($testDirectory));

        // Delete it.
        Filesystem::removeDirectory($testDirectory);

        // Check it's deleted.
        $this->assertFalse(file_exists($testDirectory));
    }

    /**
     * Test: TestRig\Services\Filesystem::execCommand().
     */
    public function testExecCommand()
    {
        // Test a failing command.
        $return = Filesystem::execCommand(
            'ls /this_does_not_exist',
            $stdout,
            $stderr
        );
        // Linux = 2; BSD/OSX = 1.
        $this->assertNotEquals(0, $return);
        $this->assertEquals('', $stdout);
        // Linux: ls: cannot access /this_does_not_exist: No such file or directory.
        // BSD/OSX: ls: /this_does_not_exist: No such file or directory
        $this->assertGreaterThan(10, strpos($stderr, 'No such file or directory'));

        // Test a successful command.
        $return = Filesystem::execCommand(
            'which sh',
            $stdout,
            $stderr
        );
        // Success is always 0, with no error.
        $this->assertEquals(0, $return);
        $this->assertEquals('', $stderr);
        $this->assertEquals('/bin/sh', trim($stdout));
    }

    /**
     * Test: TestRig\Services\Filesystem::retrieveGitstamp().
     */
    public function testRetrieveGitstamp()
    {
        $dir = static::$containingDir;
        mkdir($dir);

        Filesystem::execCommand(
            "cd $dir && git init && touch test.txt && git add . && git commit -m 'First'",
            $stdout,
            $stderr
        );
        $gitInfo = Filesystem::retrieveGitstamp($dir);

        $this->assertGreaterThan(
            0,
            strpos($stdout, substr($gitInfo['revision'], 0, 7)),
            'Could not match revision to git repository creation STDOUT.'
        );
        $this->assertEquals('master', $gitInfo['branch']);
        // Not tagged yet.
        $this->assertEquals('', $gitInfo['tag']);
        // Also no errors: the "no tags" 128 error should be caught.
        $this->assertEquals(0, $gitInfo['exitCode']);
        $this->assertEquals('', $gitInfo['error']);

        // Tag and assert it comes through.
        Filesystem::execCommand(
            "cd $dir && git tag -a 1.0.0 -m 'Tag'",
            $stdout,
            $stderr
        );
        $gitInfo = Filesystem::retrieveGitstamp($dir);

        $this->assertEquals('1.0.0', $gitInfo['tag']);
    }
}
