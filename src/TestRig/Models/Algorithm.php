<?php

/**
 * @file
 * An algorithm, stripped of its Executor's running powers.
 */

namespace TestRig\Models;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Parser;

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
     *
     * @param string $format
     *   Format of the file i.e. the suffix and hence interpreter (php, py).
     * @param UploadedFile $file
     *   Symfony uploaded file object to turn into an executable on disk.
     * @return string
     *   Folder name for the resulting algorithm bundle.
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
        $fullPath = $this->fullPath($dir);

        // Get algorithm as raw text.
        $metadata['raw'] = $this->parseFileContents(
            $fullPath,
            array("algorithm" => "algorithm")
        );

        // We're very likely to need a YAML parser.
        $yaml = new Parser();

        // Parse gitstamp into a structured array.
        if (isset($metadata['raw']['gitstamp'])) {
            $metadata["gitstamp"] = $yaml->parse($metadata["raw"]["gitstamp"]);
        }

        // Implicit file format in extension.
        foreach (glob("$fullPath/algorithm.*") as $file) {
            $fileInfo = pathinfo($file);
            $metadata['format'] = $fileInfo['extension'];
        }

        return $metadata;
    }
}
