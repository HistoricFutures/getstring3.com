<?php

/**
 * @file
 * Model to handle dataset.
 */

namespace TestRig\Models
{
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
        public function create()
        {
        }

        /**
         * Read details of a dataset and return.
         */
        public function read($path)
        {
            $full_path = $this->dir . "/" . $path . "/";
            $metadata = array();
            foreach(glob("$full_path*") as $path)
            {
                $basename = strtolower(str_replace($full_path, "", $path));
                switch ($basename)
                {
                    case "readme.txt":
                        $metadata["readme"] = file_get_contents($path);
                }
            }
            return $metadata;
        }

        /**
         * Delete dataset.
         */
        public function delete()
        {
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
    }
}
