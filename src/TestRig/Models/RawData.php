<?php

/**
 * @file
 * Raw data within a dataset.
 */

namespace TestRig\Models;

use TestRig\Exceptions\DatasetIntegrityException;
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
        // Entity summary properties.
        $entityCount = Database::getTableCount($this->path, 'entity');
        $meanAckTime = Database::getTableAggregate($this->path, 'entity', 'avg', 'mean_ack_time');
        $meanAnswerTime = Database::getTableAggregate($this->path, 'entity', 'avg', 'mean_answer_time');
        $meanRoutingTime = Database::getTableAggregate($this->path, 'entity', 'avg', 'mean_routing_time');
        $meanExtraSuppliers = Database::getTableAggregate($this->path, 'entity', 'avg', 'mean_extra_suppliers');
        $probabilityNoAck = Database::getTableAggregate($this->path, 'entity', 'avg', 'probability_no_ack');

        // Population summary properties (unique population labels).
        $populationCount = Database::getTableAggregate($this->path, 'entity', 'count', 'DISTINCT population');

        // Question/ask summary properties.
        $questionsCount = Database::getTableCount($this->path, 'question');
        $asksCount = Database::getTableCount($this->path, 'ask');

        return array(
            'entities' => array(
                'count' => $entityCount,
                'mean_ack_time' => $meanAckTime,
                'mean_answer_time' => $meanAnswerTime,
                'mean_routing_time' => $meanRoutingTime,
                'mean_extra_suppliers' => $meanExtraSuppliers,
                'probability_no_ack' => $probabilityNoAck,
            ),
            'populations' => array(
                'count' => $populationCount,
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

        // Ensure our recipe contains everything we need.
        if (!isset($recipe['questions'])) {
            throw new DatasetIntegrityException("No questions defined.");
        }
        if (!isset($recipe['populations'])) {
            throw new DatasetIntegrityException("No entity populations defined.");
        }
        // Ensure our tiers are contiguous 1..N.
        $tiers = array();
        foreach ($recipe['populations'] as $population) {
            if (isset($population['tier'])) {
                $tiers[$population['tier']] = true;
            }
        }
        for ($i = 1; $i <= count($tiers); $i++) {
            if (!isset($tiers[$i])) {
                throw new DatasetIntegrityException("Tier $i is missing from contiguous set.");
            }
        }

        // Create our entity populations.
        foreach ($recipe['populations'] as $population) {
            for ($i = 0; $i < $population['number']; $i++) {
                new Entity($this->path, null, $population);
            }
        }

        // Create our questions.
        for ($i = 0; $i < $recipe['questions']; $i++) {
            (new Question($this->path))->generateAsks();
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
