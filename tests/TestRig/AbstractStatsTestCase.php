<?php

/**
 * @file
 * Abstract test case for statistics-based tests.
 */

namespace Tests;

/**
 * @class
 * AbstractStatsTestCase.
 */
abstract class AbstractStatsTestCase extends AbstractTestCase
{
    // Thresholds for "expected randomness".
    protected $deltas = array(
        'widest' => 0.1,
        'wide' => 0.08,
        'normal' => 0.05,
        'thin' => 0.004,
        'thinnest' => 0.002,
    );
}
