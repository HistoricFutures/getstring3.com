<?php

/**
 * @file
 * An executing algorithm.
 */

namespace TestRig\Models;

use TestRig\Services\Filesystem;

/**
 * @class
 * An executing algorithm.
 */
class Executor extends Algorithm
{
    // Executables for different formats.
    private $executables = array(
        "php" => "php",
        "py" => "python",
        "sh" => "bash",
    );

    /**
     * Run algorithm against a dataset (folder).
     *
     * @param string $algorithmDir
     *   Relative path to algorithm folder.
     * @param string $datasetDir
     *   Absolute path to dataset folder.
     * @return array
     *   Exit code, stdout, stderr.
     */
    public function run($algorithmDir, $datasetPath)
    {
        // Get command to run for this executable format.
        $algorithm = $this->read($algorithmDir);
        $command = $this->executables[$algorithm['format']];
        if (!$command) {
            throw new Exception("Unrecognized algorithm format.");
        }

        // We assume absolute dataset path, but need to work out
        // from our relative algorithm dir what its full path is.
        $algorithmPath = $this->fullPath($algorithmDir) .
            "/algorithm." . $algorithm['format'];

        // Execute and return data.
        $exitCode = Filesystem::execCommand(
            "$command $algorithmPath $datasetPath",
            $stdout,
            $stderr
        );
        return array(
            "exitCode" => $exitCode,
            "stdout" => $stdout,
            "stderr" => $stderr,
        );
    }
}
