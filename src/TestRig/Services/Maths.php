<?php

/**
 * @file
 * Maths functions.
 */

namespace TestRig\Services;

/**
 * @class
 * Maths.
 */
class Maths
{
    /**
     * Returns a random variable, distributed as "fake Poissonian".
     *
     * Kroese's algorithm: see
     *  * http://www.maths.uq.edu.au/~kroese/mccourse.pdf
     *  * http://uk.mathworks.com/matlabcentral/answers/28161-poisson-random-number-generator#answer_36581
     */
    public static function poissonianNoise($mean)
    {
        $k = 1;
        $produ = 1;

        $produ *= self::evenlyRandomZeroOne();
        while ($produ >= exp(-$mean))
        {
            $produ *= self::evenlyRandomZeroOne();
            $k++;
        }

        // I believe there's a bug in the worked examples: this does not permit returning zero.
        return $k - 1;
    }

    /**
     * Returns a random variable, distributed as "fake Poissonian".
     *
     * The Poisson distribution is an asymmetric single lobe, which in the
     * CLT becomes Gaussian or Binomial.
     *
     * If we disregard values from the real Gaussian less than zero,
     * we get a simple fake Poissonian with a shifted mean. Not ideal
     * but it gets us started.
     */
    public static function fakePoissonianNoise($mean)
    {
        // TODO: only if it turns out necessary, as it's a fudge.
    }

    /**
     * Returns a random variable, normally distributed.
     *
     * Relies on Box-Muller transform:
     *   U1, U2 distributed evenly on (0, 1)
     *   Return: - sqrt(2 * ln U1) * sin(2 * pi * U2)
     * Tries again if U1=0, so not truly uniform!
     *
     * @param float $mean
     *   Mean of the underlying distribution.
     * @param float $stdDev
     *   Standard deviation (sigma) of the underlying distribution.
     * @return float
     *   Random normally-distributed result.
     */
    public static function gaussianNoise($mean, $stdDev)
    {
        // The transform actually generates two values each time, so we
        // can re-use the second value if it exists.
        static $cached;
        if ($cached)
        {
            $value = $cached * $stdDev + $mean;
            $cached = NULL;
            return $value;
        }


        // Generate two evenly random values, but require u1 nonzero for log().
        for ($u1 = 0; $u1 == 0; )
        {
            $u1 = self::evenlyRandomZeroOne();
            $u2 = self::evenlyRandomZeroOne();
        }

        // Parametrize for the formula.
        $a1 = -2 * log($u1);
        $a2 =  2 * \M_PI * $u2;

        // Map these to two valid return values, and cache one.
        $value =  sqrt($a1) * cos($a2);
        $cached = sqrt($a1) * sin($a2);

        return $value * $stdDev + $mean;
    }


    /**
     * Generate a random number between 0 and 1.
     *
     * Other methods of doing this are not ideal:
     *   * rand() is "not very" random.
     *   * lcg_value() is pseudo-random.
     * Note that it is not cryptographically random.
     *
     * @return float
     *   Random value evenly distributed on (0, 1).
     */
    public static function evenlyRandomZeroOne()
    {
        return (float)mt_rand() / (float)mt_getrandmax();
    }
}
