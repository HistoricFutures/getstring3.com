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
        foreach ($files as $file)
        {
            $callback = ($file->isDir() ? 'rmdir' : 'unlink');
            $callback($file->getRealPath());
        }

        // Recursive iterators don't include the directory, so remove.
        rmdir($dir);
    }
}
