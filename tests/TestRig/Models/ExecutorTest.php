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

    public function testRun()
    {
        // Create an algorithm and a fake dataset.
        $algorithmDir = $this->createWithMock("php", '<' . '?php echo $argv[1];');
        $datasetDir = "/tmp/for-algorithms";
        mkdir($datasetDir);

        // Run algorithm and test results.
        $results = self::$model->run($algorithmDir, $datasetDir);

        $this->assertEquals(0, $results['exitCode']);
        $this->assertEquals($datasetDir, $results['stdout']);
        $this->assertEquals("", $results['stderr']);

        Filesystem::removeDirectory($datasetDir);
    }

    /**
     * Helper: create an algorithm using mocking of UploadedFile.
     */
    private function createWithMock($format = 'php', $text = null)
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
        return self::$model->create($format, $mockUploadedFile);
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
