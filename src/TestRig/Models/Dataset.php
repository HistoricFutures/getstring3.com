<?php

/**
 * @file
 * Model to handle dataset.
 */

namespace TestRig\Models
{

    use Symfony\Component\HttpFoundation\File\UploadedFile;

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
            $dataset_dir = date("c");
            mkdir($this->dir . "/$dataset_dir");
            // Readme and BOP frmo the UploadedFile.
            file_put_contents($this->dir . "/$dataset_dir/readme.txt", "Readme");
            $file->move($this->dir . "/$dataset_dir", "bop.yaml");

            // Return directory name as a marker.
            return $dataset_dir;
        }

        /**
         * Read details of a dataset and return.
         */
        public function read($path)
        {
            $full_path = $this-fullPath($dir);
            $metadata = array();
            foreach(glob("$full_path*") as $resource)
            {
                $basename = strtolower(str_replace($full_path, "", $resource));
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
        public function delete($path)
        {
            $full_path = $this-fullPath($dir);
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
        private function fullPath($path)
        {
            return $this->dir . "/" . $path;
        }
    }
}
