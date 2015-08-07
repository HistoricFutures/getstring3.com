<?php

/**
 * @file
 * Test: TestRig\Models\Dataset.
 */

namespace Tests\Models;

use Symfony\Component\Yaml\Dumper;
use TestRig\Services\Filesystem;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Models\Dataset.
 */
class DatasetTest extends AbstractTestCase
{
    // Create a containing directory before object instantiated?
    protected static $containingDirEnvVar = 'DIR_DATASETS';

    // Do we create a testable object? Needs fully namespaced class.
    protected $testableClass = 'TestRig\Models\Dataset';

    /**
     * Test: TestRig\Models\Dataset::create().
     */
    public function testCreate()
    {
        // Create using a shared method: not ideal, but we'll need to use this
        // at the start of e.g. our index() and other methods.
        $datasetDir = $this->createWithMock();

        // Assert we've got a manifest.
        $this->assertTrue(file_exists(self::$containingDir . "/$datasetDir"));
        $this->assertTrue(file_exists(self::$containingDir . "/$datasetDir/recipe.yaml"));
        $this->assertTrue(file_exists(self::$containingDir . "/$datasetDir/dataset.sqlite3"));
        $this->assertTrue(file_exists(self::$containingDir . "/$datasetDir/gitstamp.yaml"));
    }

    /**
     * Test: TestRig\Models\Dataset::read().
     */
    public function testRead()
    {
        // Create, as above, but with a recipe.
        $recipe = "tests/fixtures/recipe.yaml";

        $datasetDir = $this->createWithMock($recipe);

        // Read the manifest.
        $dataset = $this->testable->read($datasetDir);

        // Assert manifest contents as expected.
        // Raw files all present
        $this->assertArrayHasKey("raw", $dataset);
        $this->assertArrayHasKey("gitstamp", $dataset['raw']);
        $this->assertArrayHasKey("recipe", $dataset['raw']);
        $this->assertStringEqualsFile(
            self::$containingDir . "/$datasetDir/recipe.yaml", $dataset["raw"]["recipe"]
        );

        // Gitstamp present and parsed?
        $this->assertArrayHasKey("gitstamp", $dataset);
        $this->assertArrayHasKey("revision", $dataset['gitstamp']);

        // Parsed data from recipe.yaml present?
        $this->assertarrayhaskey(0, $dataset["recipe"]["populations"]);
        $this->assertArrayHasKey(
            "number", $dataset["recipe"]["populations"][0]
        );
        $this->assertArrayHasKey("questions", $dataset["recipe"]);

        // Ensure some data in SQLite database.
        $expected = 0;
        foreach ($dataset['recipe']['populations'] as $population) {
            $expected += $population["number"];
        }
        $this->assertEquals($expected, $dataset["database"]["entities"]["count"]);
        // Ensure populations labelled.
        $this->assertEquals(count($dataset['recipe']['populations']), $dataset['database']['populations']['count']);
    }

    /**
     * Test: TestRig\Models\Dataset::delete().
     */
    public function testDelete()
    {
        // Always create.
        $datasetDir = $this->createWithMock();

        // Now delete.
        $this->testable->delete($datasetDir);

        // Assert all files are gone.
        $this->assertFalse(file_exists(self::$containingDir . "/$datasetDir"));
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
        $datasets = $this->testable->index();

        // Assert our new datasets are found.
        $this->assertContains($datasetDir, $datasets);
        $this->assertContains($datasetDir2, $datasets);
    }

    /**
     * Test: TestRig\Models\Dataset::pathToDatabase().
     */
    public function testPathToDatabase()
    {
        $path = $this->testable->pathToDatabase("foo");
        $this->assertTrue(strpos($path, "foo/dataset.sqlite3") > 0);
    }

    /**
     * Test: TestRig\Models\Dataset::readRawData().
     */
    public function testReadRawData()
    {
        $datasetDir = $this->createWithMock('tests/fixtures/recipe.yaml');
        $rawData = $this->testable->readRawData($datasetDir);
        // We should have one vertical agent in all 3 tiers, hence 32 not 30.
        $this->assertEquals(count($rawData['entity']), 32);

        // Test population labels: vertical agent will be weird.
        $verticalAgentData = array();
        foreach ($rawData['entity'] as $entityData) {
            if ($entityData['population'] == "Vertical agent") {
                $verticalAgentData[] = $entityData;
                continue;
            }

            // self_time_ratio defaults to 1.
            $this->assertEquals(1, $entityData['self_time_ratio']);

            switch ($entityData['tier']) {
            case 1:
                $this->assertEquals("Lowest tier", $entityData['population']);
                break;
            case 2:
                $this->assertEquals("Tier 2", $entityData['population']);
                break;
            case 3:
                $this->assertEquals("Tier 3", $entityData['population']);
                break;
            default:
                $this->fail("Unexpected tier in recipe.yaml.");
            }
        }

        // Check vertical agent's properties are consistent.
        for ($i = 0; $i < 3; $i++) {
            // Tiers and IDs should all be the same.
            $this->assertEquals($i+1, $verticalAgentData[$i]['tier']);
            $this->assertEquals($verticalAgentData[0]['id'], $verticalAgentData[$i]['id']);
            // self_time_ratio should not be 1, because we've changed it.
            $this->assertEquals(0.5, $verticalAgentData[$i]['self_time_ratio']);
        }
    }

    /**
     * Helper: create a dataset using mocking of UploadedFile.
     */
    private function createWithMock($recipe = null)
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
        $this->temporary_storage = $recipe;
        return $this->testable->create($mockUploadedFile);
    }

    /**
     * Helper: provide the UploadedFile mock with a ::move() method.
     *
     * This needs to touch a temporary recipe file for the dataset creation.
     * If data has been hidden on this test then it will be dumped out.
     */
    public function mockMove($dir, $file)
    {
        touch("$dir/$file");
        if ($this->temporary_storage) {
            if (is_array($this->temporary_storage)) {
                $dumper = new Dumper();
                file_put_contents(
                    "$dir/$file",
                    $dumper->dump($this->temporary_storage)
                );
            } else {
                copy($_SERVER['PWD'] . '/' . $this->temporary_storage, "$dir/$file");
            }
        }
    }
}
