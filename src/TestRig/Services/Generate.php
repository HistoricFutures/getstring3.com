<?php

/**
 * @file
 * Generate random or pseudo-random data.
 */

namespace TestRig\Services;

/**
 * @class
 * Generate.
 */
class Generate
{
    // Faker object only gets created once.
    private static $faker = null;

    /**
     * Generate a random entity name.
     */
    public static function getEntityName()
    {
        // If we've not been called before, we won't have a faker helper.
        if (!self::$faker) {
            $faker = new \Faker\Generator();
            $faker->addProvider(new \Faker\Provider\en_US\Person($faker));
            $faker->addProvider(new \Faker\Provider\en_US\Company($faker));
            self::$faker = $faker;
        }

        return self::$faker->name();
    }


    /**
     * Generate a randomized time value based on expected mean.
     *
     * Averaging over very many return values should retrieve the expectation.
     *
     * @param int $expectedMean
     *   Expected mean of the underlying population.
     * @return int
     *   Specific value chosen at random.
     */
    public static function getTime($expectedMean)
    {
        return Maths::exponentialNoise($expectedMean);
    }

    /**
     * Generate a fake-randomized probability value based on expected probability.
     *
     * Averaging over very many return values should retrieve the expectation.
     *
     * @param int $expectedMean
     *   Expected mean of the underlying population.
     * @return int
     *   Specific value chosen at random.
     */
    public static function getProbability($expectedMean)
    {
        // Pick a standard deviation that makes sense, defaulting to 0.1.
        $stdDev = 0.1;
        if ($expectedMean <= $stdDev) {
            $stdDev = 0.25 * $expectedMean;
        } elseif ($expectedMean >= 1 - $stdDev) {
            $stdDev = 0.25 * (1 - $expectedMean);
        }
        return Maths::binomialNoiseZeroOne($expectedMean, $stdDev);
    }


    /**
     * Generate a Poissonian integer with mean and upper cutoff.
     *
     * Note that the upper cutoff will inevitably shift the resulting mean.
     *
     * @param int $mean
     *   Mean of underlying Poissonian distribution.
     * @param int $cutoff = 0
     *   Maximum value (inclusive) that we can return, for finite tail.
     *   Zero means no cutoff.
     * @return int
     *   Random value.
     */
    public static function getNumber($mean, $cutoff = 0)
    {
        $value = Maths::poissonianNoise($mean);

        // If value is above cutoff, re-try.
        while ($cutoff && ($value > $cutoff)) {
            $value = Maths::poissonianNoise($mean);
        }

        return $value;
    }
}
