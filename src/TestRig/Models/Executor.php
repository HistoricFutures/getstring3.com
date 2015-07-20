<?php

/**
 * @file
 * An executing algorithm.
 */

namespace TestRig\Models;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @class
 * An executing algorithm.
 */
class Executor extends Algorithm
{
    /**
     * Run algorithm against a dataset (folder).
     *
     * @param string $datasetDir
     *   Directory of dataset.
     */
    public function run($datasetDir)
    {
        return (new Dataset())->fullPath($datasetDir);
    }
}
