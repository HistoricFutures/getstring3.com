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
     * Generate a Poisson-randomized time value based on expected mean.
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
        return Maths::poissonianNoise($expectedMean);
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
}
