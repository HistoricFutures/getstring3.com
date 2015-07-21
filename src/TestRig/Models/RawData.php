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

        $questionsCount = Database::getTableCount($this->path, 'question');
        $asksCount = Database::getTableCount($this->path, 'ask');

        return array(
            'entities' => array(
                'count' => $entityCount,
                'mean_response_time' => $meanResponseTime,
                'probability_reask' => $probabilityReask,
            ),
            'questions' => array(
                'count' => $questionsCount,
                'asks' => array(
                    'count' => $asksCount,
                ),
            ),
        );
    }

    /**
     * Populate a database with data based on a recipe.
     *
     * @param array $recipe
     *   Configuration array.
     */
    public function populate($recipe)
    {
        // Sometimes an empty or unparseable recipe is passed in.
        if (!is_array($recipe)) {
            return;
        }

        // Create our entity populations.
        if (isset($recipe['populations'])) {
            foreach ($recipe['populations'] as $population) {
                for ($i = 0; $i < $population['number']; $i++) {
                    new Entity($this->path, null, $population);
                }
            }
        }

        // Create our questions.
        if (isset($recipe['questions'])) {
            for ($i = 0; $i < $recipe['questions']; $i++) {
                (new Question($this->path))->generateAsks();
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
