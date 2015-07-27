<?php

/**
 * @file
 * Test: TestRig\Services\Filesystem.
 */

use TestRig\Services\Filesystem;

/**
 * @class
 * Test: TestRig\Services\Filesystem.
 */
class FilesystemTest extends \PHPUnit_Framework_TestCase
{
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
}
