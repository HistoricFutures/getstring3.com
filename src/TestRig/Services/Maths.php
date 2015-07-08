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
     * Generate a random number evenly between 0 and 1.
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
    
    /**
     * Generate a random number between 0 and 1, binomial-ish distribution.
     *
     * This allows us to convert an industry-average probability into assigned
     * probabilities for all agents.
     *
     * @param float $mean
     *   Underlying distribution mean between 0 and 1.
     * @param float $stdDev
     *   Underlying distribution stdDev between 0 and 1.
     * @return float
     *   Random value distributed as a binomial on (0, 1).
     */
    public static function binomialNoiseZeroOne($mean, $stdDev)
    {
        // if $mean is 0 or 1, return the same. No messing about as p(1-p) = 0.
        // Also if stdDev = 0 then there's no deviation possible.
        if (($mean === 0) || ($mean === 1) || ($stdDev === 0))
        {
            return $mean;
        }

        // If the standard deviation is greater than $mean's distance from
        // either limit, return the mean again.
        if (($mean + $stdDev >= 1) || ($mean - $stdDev <= 1))
        {
            return $mean;
        }

        // Standard deviation is deviation *in*p*, not in our hypothetical n.
        // Standard deviation in n is stdDev * n, which leads to:
        // n^2 stdDev^2 = np(1-p) => n = p(1-p)/stdDev^2 .
        $n = $mean * (1 - $mean) / pow($stdDev, 2);

        // Return binomial noise over n, divided by n (so always 0<->1).
        return self::binomialNoise($n, $mean) / $n;
    }

    /**
     * Generate a random number with binomial distribution.
     *
     * See http://math.stackexchange.com/q/788814 .
     *
     * @param float $n
     *   Number of trials.
     * @param float $p
     *   Probability of an event.
     * @return float
     *   Random number of events
     */
    public static function binomialNoise($n, $p)
    {
        $k = 0;
        for (;;)
        {
            $wait = self::binomialNoiseHelper($p);
            if ($wait > $n)
            {
                return $k;
            }
            $k++;
            $n -= $wait;
        }
    }

    /**
     * Private helper: help in generation of binomial noise.
     *
     * This could behave weird for small $p or small randomness.
     * See http://math.stackexchange.com/q/788814 .
     */
    private static function binomialNoiseHelper($p)
    {
        return ceil(log(self::evenlyRandomZeroOne())/log(1 - $p));
    }
}
