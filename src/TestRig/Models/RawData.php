<?php

/**
 * @file
 * Raw data within a dataset.
 */

namespace TestRig\Models;

use TestRig\Services\Database;

/**
 * @class
 * RawData.
 */
class RawData
{
    // Dataset path provided by constructor.
    private $path = NULL;

    /**
     * Implements ::__construct().
     *
     * @param string $datasetPath
     *   Path to dataset.
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Return summary info for the raw data.
     */
    public function getSummary()
    {
        $entityCount = Database::getTableCount($this->path, "entity");

        return array(
            "entities" => array(
                "count" => $entityCount,
            ),
        );
    }

    /**
     * Populate a database with data based on a BOP.
     *
     * @param array $bop
     *   Configuration array.
     */
    public function populate($bop)
    {
        // Sometimes an empty or unparseable BOP is passed in.
        if (!is_array($bop))
        {
            return;
        }

        // Create our entity populations.
        if (isset($bop['populations']))
        {
            foreach ($bop['populations'] as $population)
            {
                for ($i = 0; $i < $population['number']; $i++)
                {
                    $record = array("name" => "Foo");
                    Database::writeRecord($this->path, "entity", $record);
                }
            }
        }
    }
}
