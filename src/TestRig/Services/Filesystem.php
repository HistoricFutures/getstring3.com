<?php

/**
 * @file
 * Filesystem library calls.
 */

namespace TestRig\Services;

/**
 * @class
 * Filesystem methods.
 */
class Filesystem
{
    /**
     * Remove directory and all contents recursively.
     *
     * @param string $dir
     *   Top-level directory, to be removed with contents.
     */
    public static function removeDirectory($dir)
    {
        // Use directory recursion to get list of files, children first.
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        // Loop over recursive list and pick a relevant remove callback.
        foreach ($files as $file) {
            $callback = ($file->isDir() ? 'rmdir' : 'unlink');
            $callback($file->getRealPath());
        }

        // Recursive iterators don't include the directory, so remove.
        rmdir($dir);
    }

    /**
     * Execute a shell command and provide STDOUT, STDERR etc.
     *
     * See http://stackoverflow.com/a/25879953/327153 .
     *
     * @param string $cmd
     *                    Shell command.
     * @param string &stdout = null
     *   Target string for STDOUT.
     * @param string &stderr = null
     *   Target string for STDERR.
     *
     * @return int
     *             Exit code.
     */
    public static function execCommand($cmd, &$stdout = null, &$stderr = null)
    {
        $proc = proc_open($cmd, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($proc);
    }
}
