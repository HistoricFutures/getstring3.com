<?php

/**
 * @file
 * An algorithm, stripped of its Executor's running powers.
 */

namespace TestRig\Models;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @class
 * Algorithm.
 */
class Algorithm extends AbstractFolderManager
{
    // Environment variable to be set per-extended class.
    protected $dirEnvVar = 'DIR_ALGORITHMS';

    /**
     * Create an algorithm.
     */
    public function create($format, UploadedFile $file)
    {
        // Parent creates the folder and gets naming right.
        $dir = parent::create();
        // Add the algorithm file in the right format.
        $file->move($this->rootDir . "/$dir", "algorithm.$format");

        return $dir;
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
            array("algorithm" => "algorithm")
        );

        return $metadata;
    }
}
