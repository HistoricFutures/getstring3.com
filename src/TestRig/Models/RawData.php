<?php

/**
 * @file
 * Raw data within a dataset.
 */

namespace TestRig\Models;

use TestRig\Exceptions\MissingTableException;
use TestRig\Models\Entity;
use TestRig\Services\Database;

/**
 * @class
 * RawData.
 */
class RawData
{
    // Dataset path provided by constructor.
    private $path = null;

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
        $meanResponseTime = Database::getTableAggregate($this->path, 'entity', 'avg', 'mean_response_time');
        $probabilityReask = Database::getTableAggregate($this->path, 'entity', 'avg', 'probability_reask');

        // Older datasets have no asks.
        try {
            $asksCount = Database::getTableCount($this->path, 'ask');
            $actionsCount = Database::getTableCount($this->path, 'action');
        } catch (MissingTableException $e) {
            $asksCount = null;
            $actionsCount = null;
        }

        return array(
            'entities' => array(
                'count' => $entityCount,
                'mean_response_time' => $meanResponseTime,
                'probability_reask' => $probabilityReask,
            ),
            'asks' => array(
                'count' => $asksCount,
                'actions' => array(
                    'count' => $actionsCount,
                ),
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
        if (!is_array($bop)) {
            return;
        }

        // Create our entity populations.
        if (isset($bop['populations'])) {
            foreach ($bop['populations'] as $population) {
                for ($i = 0; $i < $population['number']; $i++) {
                    new Entity($this->path, null, $population);
                }
            }
        }

        // Create our asks.
        if (isset($bop['asks'])) {
            for ($i = 0; $i < $bop['asks']; $i++) {
                (new Ask($this->path))->generateActions();
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
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
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
        if (isset($options['entity'])) {
            switch ($options['entity']) {
            case 'all':
                $sql = 'SELECT * FROM entity ORDER BY id';
                break;

            default:
                $sql = 'SELECT COUNT(*) AS count FROM entity';
            }

            $results = Database::getConn($this->path)->query($sql);
            while ($row = $results->fetchArray()) {
                $export['entity'][] = $row;
            }
        }

        return $export;
    }
}
