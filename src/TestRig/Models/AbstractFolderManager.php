<?php

/**
 * @file
 * Abstract representation of handler of its own folder and contents.
 */

namespace TestRig\Models;

use TestRig\Services\Filesystem;

/**
 * @class
 * Abstract representation of handler of its own folder and contents.
 */
abstract class AbstractFolderManager
{
    // Directory datasets stored in: only override via environment.
    protected $dir;
    // Environment variable key for above, to be set per-extended class.
    protected $dirEnvVar = null;
    
    /**
     * Implements ::__construct().
     */
    public function __construct()
    {
        if ($this->dirEnvVar === null) {
            throw new Exception("Need to initialize environment variable used to locate files.");
        }

        // If environment variable not absolute path, append the server
        // document root variable to the start.
        $this->dir = getenv($this->dirEnvVar);
        if (strpos($this->dir, "/") !== 0) {
            $this->dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->dir;
        }
    }

    /**
     * Create a containing folder.
     */
    public function create()
    {
        // Directory name based on the current date/time.
        $dir = date("c");
        $fullPath = $this->fullPath($dir);
        // If we really have a race condition, append a random string.
        // Process ID no good, as we could be creating during same process.
        if (file_exists($fullPath)) {
            $dir .= "-" . rand(1, 32767);
            $fullPath = $this->fullPath($dir);
        }

        // Start folder on disk and make known what folder it's in.
        mkdir($this->dir . "/$dir");
        return $dir;
    }

    /**
     * Read contents of a containing folder and act accordingly.
     *
     * This is so different for each extended class that we just make this
     * method abstract and leave it to the child class.
     */
    abstract public function read($dir);

    /**
     * Delete folder and all its contents.
     */
    public function delete($dir)
    {
        $fullPath = $this->fullPath($dir);
        Filesystem::removeDirectory($fullPath);
    }

    /**
     * Index of managed folders.
     */
    public function index()
    {
        $paths = glob("$this->dir/*");
        $managedFolders = array();
        foreach ($paths as $path) {
            $managedFolders[] = str_replace($this->dir . "/", "", $path);
        }
        return $managedFolders;
    }

    /**
     * Protected: return full path to a managed folder.
     */
    protected function fullPath($dir)
    {
        return $this->dir . "/$dir";
    }
}
