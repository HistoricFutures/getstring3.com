<?php

/**
 * @file
 * Test: TestRig\Models\Algorithm.
 */

namespace Tests\Models;

use Symfony\Component\Yaml\Dumper;
use TestRig\Services\Filesystem;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Models\Algorithm.
 */
class AlgorithmTest extends AbstractTestCase
{
    // To be replaced with new Algorithm() during setUpBeforeClass.
    public static $model = null;
    // Root directory for folders: we keep track too.
    private static $rootDir = null;

    // Do we create a testable object? Needs fully namespaced class.
    protected $testableClass = 'TestRig\Models\Algorithm';

    /**
     * Set up before class: create root folder and Algorithm() handler class.
     */
    public static function setUpBeforeClass()
    {
        self::$rootDir = getenv('DIR_ALGORITHMS');
        mkdir(self::$rootDir);
    }

    /**
     * Tear down after class: delete root folder.
     */
    public static function tearDownAfterClass()
    {
        Filesystem::removeDirectory(self::$rootDir);
    }

    /**
     * Test: TestRig\Models\Algorithm::create().
     */
    public function testCreate()
    {
        // Create using a shared method: not ideal, but we'll need to use this
        // at the start of e.g. our index() and other methods.
        $dir = $this->createWithMock();

        // Assert we've got a manifest.
        $this->assertTrue(file_exists(self::$rootDir . "/$dir"));
        $this->assertTrue(file_exists(self::$rootDir . "/$dir/algorithm.php"));

        // Create with a different format.
        $dir = $this->createWithMock("py");
        $this->assertTrue(file_exists(self::$rootDir . "/$dir/algorithm.py"));
    }

    /**
     * Test: TestRig\Models\Algorithm::read().
     */
    public function testRead()
    {
        $dir = $this->createWithMock();

        // Read the manifest.
        $algorithm = $this->testable->read($dir);
        // Assert manifest contents as expected.
        // Raw file present.
        $this->assertArrayHasKey("raw", $algorithm);
        $this->assertArrayHasKey("algorithm", $algorithm['raw']);

        $this->assertArrayHasKey("format", $algorithm);
        $this->assertEquals("php", $algorithm["format"]);

        // Create a Python algorithm.
        $dir = $this->createWithMock("py");

        // Read the manifest.
        $algorithm = $this->testable->read($dir);
        $this->assertEquals("py", $algorithm["format"]);
    }

    /**
     * Test: TestRig\Models\Algorithm::delete().
     */
    public function testDelete()
    {
        // Always create.
        $dir = $this->createWithMock();

        // Now delete.
        $this->testable->delete($dir);

        // Assert all files are gone.
        $this->assertFalse(file_exists(self::$rootDir . "/$dir"));
    }
    
    /**
     * Test: TestRig\Models\Algorithm::index().
     */
    public function testIndex()
    {
        // Always create: a couple, for the index.
        $dir = $this->createWithMock();
        $dir2 = $this->createWithMock();

        // Obtain an index.
        $folders = $this->testable->index();

        // Assert our new folders are found.
        $this->assertContains($dir, $folders);
        $this->assertContains($dir2, $folders);
    }

    /**
     * Helper: create an algorithm using mocking of UploadedFile.
     */
    private function createWithMock($format = 'php')
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

        // Squirrel away the data array, so our mock callback can
        // access it later on and clear it.
        $this->temporary_mock_storage = "<" . "?php echo 'foo'; ";
        return $this->testable->create($format, $mockUploadedFile);
    }

    /**
     * Helper: provide the UploadedFile mock with a ::move() method.
     *
     * This needs to touch a temporary recipe file for the folder creation.
     * If data has been hidden on this test then it will be dumped out.
     */
    public function mockMove($dir, $file)
    {
        touch("$dir/$file");
        if ($this->temporary_mock_storage) {
            file_put_contents("$dir/$file", $this->temporary_mock_storage);
        }
    }
}
