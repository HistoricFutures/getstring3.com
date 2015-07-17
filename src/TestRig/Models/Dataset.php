<?php

/**
 * @file
 * Model to handle dataset.
 */

namespace TestRig\Models;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Parser;
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
     * Create a dataset.
     */
    public function create(UploadedFile $file)
    {
        $datasetDir = parent::create();

        // Readme and BOP from the UploadedFile.
        file_put_contents($this->rootDir . "/$datasetDir/readme.txt", "Readme");
        $file->move($this->rootDir . "/$datasetDir", "bop.yaml");
        // SQLite database create and generate schema.
        $databasePath = $this->pathToDatabase($datasetDir);
        Database::create($databasePath);
        // Populate database with raw data based on the BOP.
        $bopParsed = $this->getAndParseBOP($datasetDir);
        (new RawData($databasePath))->populate($bopParsed['yaml']);

        // Return directory name as a marker.
        return $datasetDir;
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
            array('readme' => 'readme.txt', 'bop' => 'bop.yaml')
        );

        // Parse any existing BOP into a structured array.
        if (isset($metadata['raw']['bop'])) {
            $yaml = new Parser();
            $metadata["bop"] = $yaml->parse($metadata["raw"]["bop"]);
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
     * Get BOP file and parse into YAML.
     *
     * @param string $datasetDir
     *   Path to dataset directory.
     * @return array
     *   BOP file text, and parsed YAML.
     */
    public function getAndParseBOP($datasetDir)
    {
        $bopText = file_get_contents($this->fullPath($datasetDir) . "/bop.yaml");
        $yaml = new Parser();
        return array(
            "yaml" => $yaml->parse($bopText),
            "text" => $bopText,
        );
    }

    /**
     * Get (some) raw data via RawData.
     */
    public function readRawData($datasetDir)
    {
        $rawData = new RawData($this->pathToDatabase($datasetDir));
        return $rawData->export(array("entity" => "all"));
    }
}
