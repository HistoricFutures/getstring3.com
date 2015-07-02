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
        public function read()
        {
        }

        /**
         * Delete dataset.
         */
        public function delete()
        {
        }
    }
}
