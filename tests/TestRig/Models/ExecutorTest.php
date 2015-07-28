<?php

/**
 * @file
 * Test: TestRig\Models\Executor.
 */

namespace Tests\Models;

use TestRig\Services\Filesystem;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Models\Executor.
 */
class ExecutorTest extends AbstractTestCase
{
    // Create a containing directory before object instantiated?
    protected static $containingDirEnvVar = 'DIR_ALGORITHMS';

    // Do we create a testable model?
    protected $testableClass = 'TestRig\Models\Executor';

    /**
     * Test: TestRig\Models\Executor::run().
     */
    public function testRun()
    {
        // Create an algorithm and a fake dataset.
        $algorithmDir = $this->createWithMock("php", '<' . '?php echo $argv[1];');
        $datasetDir = "/tmp/this-never-gets-accessed-anyway";

        // Run algorithm and test results.
        $results = $this->testable->run($algorithmDir, $datasetDir);

        $this->assertEquals(0, $results['exitCode']);
        $this->assertEquals($datasetDir, $results['stdout']);
        $this->assertEquals("", $results['stderr']);
    }

    /**
     * Helper: create an algorithm using mocking of UploadedFile.
     */
    protected function createWithMock($format = 'php', $text = null)
    {
        // Mock up an UploadedFile, disabling its constructor.
        $mockBuilder = $this->getMockBuilder(
            'Symfony\Component\HttpFoundation\File\UploadedFile'
        );
        $mockBuilder->disableOriginalConstructor();
        $mockBuilder->setMethods(array("move"));

        // Generate a mock object and call create.
        $mockUploadedFile = $mockBuilder->getMock();
        // Add a method call for ->move($targetDir, $targetFilename).
        $mockUploadedFile->expects($this->once())->method("move")
            ->will($this->returnCallback(array($this, 'mockMove')));

        // Squirrel away the bop data array, so our mock callback can
        // access it later on and clear it.
        $this->temporary_mock_storage = isset($text) ? $text : '<' . '?php echo "foo"; ';
        return $this->testable->create($format, $mockUploadedFile);
    }

    /**
     * Helper: provide the UploadedFile mock with a ::move() method.
     *
     * This needs to touch a temporary BOP file for the folder creation.
     * If BOP data has been hidden on this test then it will be dumped out.
     */
    public function mockMove($dir, $file)
    {
        touch("$dir/$file");
        if ($this->temporary_mock_storage) {
            file_put_contents("$dir/$file", $this->temporary_mock_storage);
        }
    }
}
