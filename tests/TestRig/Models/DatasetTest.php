<?php

/**
 * @file
 * Test: TestRig\Models\Dataset.
 */

use Symfony\Component\Yaml\Dumper;
use TestRig\Models\Dataset;
use TestRig\Services\Filesystem;

/**
 * @class
 * Test: TestRig\Models\Dataset.
 */
class DatasetTest extends \PHPUnit_Framework_TestCase
{
    // To be replaced with new Dataset() during setUpBeforeClass.
    public static $model = NULL;
    // Directory for datasets: we keep track too.
    private static $dir = NULL;

    /**
     * Set up before class: create dataset folder and Dataset() handler class.
     */
    public static function setUpBeforeClass()
    {
        self::$dir = getenv('DIR_DATASETS');
        mkdir(self::$dir);
        self::$model = new Dataset();
    }

    /**
     * Tear down after class: delete dataset folder.
     */
    public static function tearDownAfterClass()
    {
        Filesystem::removeDirectory(self::$dir);
    }

    /**
     * Test: TestRig\Models\Dataset::create().
     */
    public function testCreate()
    {
        // Create using a shared method: not ideal, but we'll need to use this
        // at the start of e.g. our index() and other methods.
        $datasetDir = $this->createWithMock();

        // Assert we've got a manifest.
        $this->assertTrue(file_exists(self::$dir . "/$datasetDir"));
        $this->assertTrue(file_exists(self::$dir . "/$datasetDir/readme.txt"));
        $this->assertTrue(file_exists(self::$dir . "/$datasetDir/bop.yaml"));
    }

    /**
     * Test: TestRig\Models\Dataset::read().
     */
    public function testRead()
    {
        // Create, as above, but with a BOP.
        $bop = array("organizations" => array(array("prefix" => "Foo")));

        $datasetDir = $this->createWithMock($bop);

        // Read the manifest.
        $dataset = self::$model->read($datasetDir);

        // Assert manifest contents as expected.
        // Raw files both present.
        $this->assertArrayHasKey("raw", $dataset);
        $this->assertArrayHasKey("readme", $dataset['raw']);
        $this->assertArrayHasKey("bop", $dataset['raw']);
        $this->assertStringEqualsFile(
          self::$dir . "/$datasetDir/readme.txt", $dataset["raw"]["readme"]
        );
        $this->assertStringEqualsFile(
          self::$dir . "/$datasetDir/bop.yaml", $dataset["raw"]["bop"]
        );

        // Parsed data from bop.yaml present?
        $this->assertArrayHasKey("bop", $dataset);
        $this->assertArrayHasKey("organizations", $dataset["bop"]);
        $this->assertArrayHasKey(0, $dataset["bop"]["organizations"]);
        $this->assertArrayHasKey(
            "prefix", $dataset["bop"]["organizations"][0]
        );
        $this->assertEquals(
            $dataset["bop"]["organizations"][0]["prefix"],
            $bop["organizations"][0]["prefix"]
        );
    }

    /**
     * Test: TestRig\Models\Dataset::delete().
     */
    public function testDelete()
    {
        // Always create.
        $datasetDir = $this->createWithMock();

        // Now delete.
        self::$model->delete($datasetDir);

        // Assert all files are gone.
        $this->assertFalse(file_exists(self::$dir . "/$datasetDir"));
    }
    
    /**
     * Test: TestRig\Models\Dataset::index().
     */
    public function testIndex()
    {
        // Always create: a couple, for the index.
        $datasetDir = $this->createWithMock();
        $datasetDir2 = $this->createWithMock();

        // Obtain an index.
        $datasets = self::$model->index();

        // Assert our new datasets are found.
        $this->assertContains($datasetDir, $datasets);
        $this->assertContains($datasetDir2, $datasets);
    }

    /**
     * Helper: create a dataset using mocking of UploadedFile.
     */
    private function createWithMock($bop = NULL) {
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
        $this->temporary_bop_storage = $bop;
        return self::$model->create($mockUploadedFile);
    }

    /**
     * Helper: provide the UploadedFile mock with a ::move() method.
     *
     * This needs to touch a temporary BOP file for the dataset creation.
     * If BOP data has been hidden on this test then it will be dumped out.
     */
    public function mockMove($dir, $file)
    {
        touch("$dir/$file");
        if ($this->temporary_bop_storage) {
            $dumper = new Dumper();
            file_put_contents(
                "$dir/$file",
                $dumper->dump($this->temporary_bop_storage)
            );
        }
    }
}
