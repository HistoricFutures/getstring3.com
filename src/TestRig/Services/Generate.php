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
    private static $faker = NULL;

    /**
     * Generate a random entity name.
     */
    public static function getEntityName()
    {
        // If we've not been called before, we won't have a faker helper.
        if (!self::$faker)
        {
            $faker = new \Faker\Generator();
            $faker->addProvider(new \Faker\Provider\en_US\Person($faker));
            $faker->addProvider(new \Faker\Provider\en_US\Company($faker));
            self::$faker = $faker;
        }

        return self::$faker->company();
    }
}
