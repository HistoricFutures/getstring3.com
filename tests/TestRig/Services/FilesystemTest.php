<?php

use TestRig\Services\Filesystem;

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
}
