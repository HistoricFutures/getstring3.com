<?php

/**
 * @file
 * Test: TestRig\Services\Generate.
 */

namespace Tests\Services;

use TestRig\Services\Generate;
use Tests\AbstractStatsTestCase;

/**
 * @class
 * Test: TestRig\Services\Generate.
 */

class GenerateTest extends AbstractStatsTestCase
{
    /**
     * Test: \TestRig\Services\Generate::getEntityName().
     */
    public function testGetEntityName()
    {
        $name1 = Generate::getEntityName();
        $name2 = Generate::getEntityName();
        $this->assertNotEquals($name1, $name2);
    }

    /**
     * Test: \TestRig\Services\Generate::getTime().
     */
    public function testGetTime()
    {
        $mean = 50;
        $trials = 1000;

        // Super-difficult to test as it's not very narrow.
        // Test floatiness, then get lots of values and test average.
        $value = Generate::getTime($mean);
        $this->assertTrue(is_float($value));

        // We already have one value, so get 999 more.
        for ($i = 1; $i < $trials; $i++) {
            $value += Generate::getTime($mean);
        }
        $this->assertGreaterThanOrEqual(
            $mean * (1 - $this->deltas['widest']),
            $value / $trials,
            "Noise value (unluckily?) low: test again?"
        );
        $this->assertLessThanOrEqual(
            $mean * (1 + $this->deltas['widest']),
            $value / $trials,
            "Noise value (unluckily?) high: test again?"
        );
    }

    /**
     * Test: \TestRig\Services\Generate::getProbability().
     */
    public function testGetProbability()
    {
        $mean = 0.7;
        $stdDev = 0.2;
        $trials = 1000;

        // Ensure we get a float back.
        $value = Generate::getProbability($mean, $stdDev);
        $this->assertTrue(is_float($value));
        // We already have one value, so get 999 more.
        for ($i = 1; $i < $trials; $i++) {
            $value += Generate::getProbability($mean, $stdDev);
        }

        $this->assertGreaterThanOrEqual(
            $mean * (1 - $this->deltas['wide']),
            $value / $trials,
            "Binomial 0-1 noise value (unluckily?) low: test again?"
        );
        $this->assertLessThanOrEqual(
            $mean * (1 + $this->deltas['wide']),
            $value / $trials,
            "Binomial 0-1 noise value (unluckily?) high: test again?"
        );
    }

    /**
     * Test: \TestRig\Services\Generate::getNumber().
     */
    public function testGetNumber()
    {
        // If cutoff isn't working, a high mean will show it up.
        $mean = 10;
        $cutoff = 5;
        $this->assertLessThanOrEqual($cutoff, Generate::getNumber($mean, $cutoff));

        // Remove cutoff and check regression to mean.
        $value = 0;
        $trials = 100;
        for ($i = 1; $i <= $trials; $i++) {
            $value += Generate::getNumber($mean);
        }

        $this->assertGreaterThanOrEqual(
            $mean * (1 - $this->deltas['normal']),
            $value / $trials,
            "Binomial 0-1 noise value (unluckily?) low: test again?"
        );
        $this->assertLessThanOrEqual(
            $mean * (1 + $this->deltas['normal']),
            $value / $trials,
            "Binomial 0-1 noise value (unluckily?) high: test again?"
        );
    }
}
