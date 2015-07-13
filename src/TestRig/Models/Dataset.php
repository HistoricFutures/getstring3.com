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
use TestRig\Services\Filesystem;

/**
 * @class
 * Represent a dataset on disk.
 */
class Dataset
{
    // Directory datasets stored in: only override via environment.
    private $dir;

    /**
     * Implements ::__construct().
     */
    public function __construct()
    {
        // If environment variable not absolute path, append the server
        // document root variable to the start.
        $this->dir = getenv('DIR_DATASETS');
        if (strpos($this->dir, "/") !== 0) {
            $this->dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->dir;
        }
    }

    /**
     * Create a dataset.
     */
    public function create(UploadedFile $file)
    {
        // Directory name based on the current date/time.
        $datasetDir = date("c");
        $fullPath = $this->fullPath($datasetDir);
        // If we really have a race condition, append a random string.
        // Process ID no good, as we could be creating during same process.
        if (file_exists($fullPath)) {
            $datasetDir .= "-" . rand(1, 32767);
            $fullPath = $this->fullPath($datasetDir);
        }

        // Start dataset folder on disk.
        mkdir($this->dir . "/$datasetDir");
        // Readme and BOP from the UploadedFile.
        file_put_contents($this->dir . "/$datasetDir/readme.txt", "Readme");
        $file->move($this->dir . "/$datasetDir", "bop.yaml");
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
     * Read details of a dataset and return.
     */
    public function read($datasetDir)
    {
        $fullPath = $this->fullPath($datasetDir);
        $metadata = array();

        foreach (glob("$fullPath/*") as $resource) {
            $basename = strtolower(str_replace("$fullPath/", "", $resource));
            switch ($basename) {
                // Put raw file contents into the 'raw' array.
                case "readme.txt":
                    $metadata["raw"]["readme"] = file_get_contents($resource);
                    break;

                // Raw bop.yaml but also parse it for info.
                case "bop.yaml":
                    $metadata["raw"]["bop"] = file_get_contents($resource);
                    $yaml = new Parser();
                    $metadata["bop"] = $yaml->parse($metadata["raw"]["bop"]);
            }
        }

        // SQLite database: connect and get info.
        $rawData = new RawData($this->pathToDatabase($datasetDir));
        $metadata["database"] = $rawData->getSummary();

        return $metadata;
    }

    /**
     * Delete dataset.
     */
    public function delete($datasetDir)
    {
        $fullPath = $this->fullPath($datasetDir);
        Filesystem::removeDirectory($fullPath);
    }

    /**
     * Index of datasets.
     */
    public function index()
    {
        $paths = glob("$this->dir/*");
        $datasets = array();
        foreach ($paths as $path) {
            $datasets[] = str_replace($this->dir . "/", "", $path);
        }
        return $datasets;
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

    /**
     * Private: return full path to a dataset.
     */
    private function fullPath($datasetDir)
    {
        return $this->dir . "/$datasetDir";
    }
}
