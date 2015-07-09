<?php

/**
 * @file
 * Raw data within a dataset.
 */

namespace TestRig\Models;

use TestRig\Models\Entity;
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
        $entityCount = Database::getTableCount($this->path, 'entity');

        return array(
            'entities' => array(
                'count' => $entityCount,
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
                    new Entity($this->path, NULL, $population);
                }
            }
        }
    }

    /**
     * Get all entities.
     */
    public function getEntities()
    {
        $entities = array();
        $results = Database::getConn($this->path)->query('SELECT * FROM entity ORDER BY id');
        while ($row = $results->fetchArray(SQLITE3_ASSOC))
        {
            $entities[$row['id']] = $row;
        }
        return $entities;
    }

    /**
     * Export data or metrics selectively from tables.
     */
    public function export($options)
    {
        $export = array();

        // Only work on tables which exist: also avoids having to worry about
        // string escaping at this layer.
        if (isset($options['entity']))
        {
            switch($options['entity'])
            {
            case 'all':
                $sql = 'SELECT * FROM entity ORDER BY id';
                break;

            default:
                $sql = 'SELECT COUNT(*) AS count FROM entity';
            }

            $results = Database::getConn($this->path)->query($sql);
            while ($row = $results->fetchArray())
            {
                $export['entity'][] = $row;
            }
        }

        return $export;
    }
}
