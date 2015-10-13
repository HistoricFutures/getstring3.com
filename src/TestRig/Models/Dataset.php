<?php

/**
 * @file
 * Model to handle dataset.
 */

namespace TestRig\Models;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Parser;
use TestRig\Exceptions\MissingFileException;
use TestRig\Models\RawData;
use TestRig\Services\Database;

/**
 * @class
 * Represent a dataset on disk.
 */
class Dataset extends AbstractFolderManager
{
    // Environment variable to be set per-extended class.
    protected $dirEnvVar = 'DIR_DATASETS';

    /**
     * Create a dataset from a HTTP form's UploadedFile.
     *
     * @param UploadedFile $file
     *   File object.
     * @return string
     *   Location of directory containing dataset.
     */
    public function create(UploadedFile $file)
    {
        $datasetDir = parent::create();

        // Recipe from the UploadedFile.
        $file->move($this->rootDir . "/$datasetDir", "recipe.yaml");
        // Now create all other files.
        $this->createAllOtherFiles($datasetDir);

        // Return directory name as a marker.
        return $datasetDir;
    }

    /**
     * Create a dataset from a filename.
     *
     * @param string $filename
     *   Filename, relative to current directory.
     * @return string
     *   Location of directory containing dataset.
     */
    public function createFromFilename($filename)
    {
        $fullPath = realpath($filename);
        if (!file_exists($fullPath)) {
            throw new MissingFileException($fullPath);
        }

        $datasetDir = parent::create();

        // Recipe from the filename.
        copy($fullPath, $this->rootDir . "/$datasetDir/recipe.yaml");
        // Now create all other files.
        $this->createAllOtherFiles($datasetDir);

        // Return directory name as a marker.
        return $datasetDir;
    }

    /**
     * Once creation has begun from recipe, create all other files.
     *
     * @param string $datasetDir
     *   Dataset directory containing recipe.
     */
    private function createAllOtherFiles($datasetDir)
    {
        // SQLite database create and generate schema.
        $databasePath = $this->pathToDatabase($datasetDir);
        Database::create($databasePath);
        // Populate database with raw data based on the recipe.
        $recipeParsed = $this->getAndParseRecipe($datasetDir);
        (new RawData($databasePath))->populate($recipeParsed['yaml']);
    }

    /**
     * Implements ::read().
     *
     * Overrides abstract method to read details of an algorithm.
     */
    public function read($dir)
    {
        $metadata['raw'] = $this->parseFileContents(
            $this->fullPath($dir),
            array('readme' => 'readme.txt', 'recipe' => 'recipe.yaml')
        );

        // We're very likely to need a YAML parser.
        $yaml = new Parser();

        // Parse gitstamp into a structured array.
        if (isset($metadata['raw']['gitstamp'])) {
            $metadata["gitstamp"] = $yaml->parse($metadata["raw"]["gitstamp"]);
        }
        // Parse any existing recipe into a structured array.
        if (isset($metadata['raw']['recipe'])) {
            $metadata["recipe"] = $yaml->parse($metadata["raw"]["recipe"]);
        }

        // SQLite database: connect and get info.
        $rawData = new RawData($this->pathToDatabase($dir));
        $metadata["database"] = $rawData->getSummary();

        return $metadata;
    }

    /**
     * Path to database SQLite file.
     *
     * Used by e.g. RawData to connect to the database.
     *
     * @return string
     *   Absolute path to file.
     */
    public function pathToDatabase($datasetDir)
    {
        $fullPath = $this->fullPath($datasetDir);
        return "$fullPath/dataset.sqlite3";
    }

    /**
     * Get recipe file and parse into YAML.
     *
     * @param string $datasetDir
     *   Path to dataset directory.
     * @return array
     *   Recipe file text, and parsed YAML.
     */
    public function getAndParseRecipe($datasetDir)
    {
        $recipeText = file_get_contents($this->fullPath($datasetDir) . "/recipe.yaml");
        $yaml = new Parser();
        return array(
            "yaml" => $yaml->parse($recipeText),
            "text" => $recipeText,
        );
    }

    /**
     * Get (some) raw data via RawData.
     */
    public function readRawData($datasetDir)
    {
        $rawData = new RawData($this->pathToDatabase($datasetDir));
        return $rawData->export(array("entity" => "extended"));
    }
}
