<?php

/**
 * @file
 * Model to handle dataset.
 */

namespace TestRig\Models;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use TestRig\Services\Filesystem;

/**
 * @class
 * Represent a dataset on disk.
 */
class Dataset
{
    /**
     * Implements ::__construct().
     */
    public function __construct()
    {
        $this->dir = $_SERVER['DOCUMENT_ROOT'] . '/' . getenv('DIR_DATASETS');
    }

    /**
     * Create a dataset.
     */
    public function create(UploadedFile $file)
    {
        // Directory name based on the current date/time.
        $datasetDir = date("c");
        mkdir($this->dir . "/$datasetDir");
        // Readme and BOP frmo the UploadedFile.
        file_put_contents($this->dir . "/$datasetDir/readme.txt", "Readme");
        $file->move($this->dir . "/$datasetDir", "bop.yaml");

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
        foreach(glob("$fullPath/*") as $resource)
        {
            $basename = strtolower(str_replace("$fullPath/", "", $resource));
            switch ($basename)
            {
                case "readme.txt":
                    $metadata["readme"] = file_get_contents($resource);
            }
        }
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
        foreach ($paths as $path)
        {
          $datasets[] = str_replace($this->dir . "/", "", $path);
        }
        return $datasets;
    }

    /**
     * Private: return full path to a dataset.
     */
    private function fullPath($datasetDir)
    {
        return $this->dir . "/$datasetDir";
    }
}
