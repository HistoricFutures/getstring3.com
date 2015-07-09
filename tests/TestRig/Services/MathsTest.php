<?php

/**
 * @file
 * Test: TestRig\Services\Maths.
 */

use TestRig\Services\Maths;

/**
 * @class
 * Test: TestRig\Services\Maths.
 */
class MathsTest extends \PHPUnit_Framework_TestCase
{
    // Thresholds for "expected randomness".
    // Poissonian is generally wider as stddev = mean unavoidably.
    private $poissonianDelta = 0.05;
    private $gaussianDelta = 0.004;
    private $binomialDelta = 0.002;
    // Quite wide standard deviation on the 0-1 binomial.
    private $binomialZeroOneDelta = 0.08;

    /**
     * Test: TestRig\Services\Maths::testPoissonianNoise().
     */
    public function testPoissonianNoise()
    {
        $mean = 50;
        $trials = 1000;

        // Super-difficult to test as it's not very narrow.
        // Test integeriness, then get lots of values and test average.
        $value = Maths::poissonianNoise($mean);
        $this->assertTrue(is_int($value));

        // We already have one value, so get 999 more.
        for ($i = 0; $i < $trials; $i++)
        {
            $value += Maths::poissonianNoise($mean);
        }
        $this->assertGreaterThanOrEqual(
            $mean * (1 - $this->poissonianDelta),
            $value / $trials,
            "Poissonian noise value (unluckily?) low: test again?"
        );
        $this->assertLessThanOrEqual(
            $mean * (1 + $this->poissonianDelta),
            $value / $trials,
            "Poissonian noise value (unluckily?) high: test again?"
        );
    }

    /**
     * Test: TestRig\Services\Maths::testFakePoissonianNoise().
     */
    public function testFakePoissonianNoise()
    {
        // This function should be todo, so expect NULL.  If the function is
        // "woken up", then this test will fail and need to be written!
        $this->assertNull(Maths::fakePoissonianNoise(5));
    }

    /**
     * Test: TestRig\Services\Maths::testGaussianNoise().
     */
    public function testGaussianNoise()
    {
        $mean = 500;

        // Very difficult to test as it has no limits, so test float-ness and
        // narrowness of distribution.  This test CAN randomly fail, but very unlikely.
        $value = Maths::gaussianNoise($mean, 0.1);
        $this->assertTrue(is_float($value));
        $this->assertNotEquals($mean, $value, "Gaussian noise equals the mean. Unlucky?");

        $this->assertGreaterThanOrEqual(
            $mean * (1 - $this->gaussianDelta), 
            $value,
            "Gaussian noise value (unluckily?) low: test again?"
        );
        $this->assertLessThanOrEqual(
            $mean * (1 + $this->gaussianDelta),
            $value,
            "Gaussian noise value (unluckily?) high: test again?"
        );
    }

    /**
     * Test: TestRig\Services\Maths::testEvenlyRandomZeroOne().
     */
    public function testEvenlyRandomZeroOne()
    {
        // Can't test much, except its float-ness and limits.
        $value = Maths::evenlyRandomZeroOne();
        $this->assertTrue(is_float($value));
        $this->assertGreaterThanOrEqual(0, $value);
        $this->assertLessThanOrEqual(1, $value);
    }

    /**
     * Test: TestRig\Services\Maths::testBinomialNoiseZeroOne().
     */
    public function testBinomialNoiseZeroOne()
    {
        $mean = 0.7;
        $stdDev = 0.2;
        $trials = 1000;

        // 0 and 1 should return 0 and 1 always.
        $this->assertEquals(0, Maths::binomialNoiseZeroOne(0, 0.2));
        $this->assertEquals(1, Maths::binomialNoiseZeroOne(1, 0.2));

        // Invalidly high standard deviation should return the mean.
        $this->assertEquals(0.7, Maths::binomialNoiseZeroOne(0.7, 0.4));
        $this->assertEquals(0.2, Maths::binomialNoiseZeroOne(0.2, 0.3));

        // Ensure we get a float back.
        $value = Maths::binomialNoiseZeroOne($mean, $stdDev);
        $this->assertTrue(is_float($value));
        // And it should almost never (?) be exactly the mean.
        $this->assertNotEquals($mean, $value, "Binomial noise 0-1 returned exactly the mean. Unlucky?");

        // We already have one value, so get 999 more.
        for ($i = 0; $i < $trials; $i++)
        {
            $value += Maths::binomialNoiseZeroOne($mean, $stdDev);
        }

        $this->assertGreaterThanOrEqual(
            $mean * (1 - $this->binomialZeroOneDelta),
            $value / $trials,
            "Binomial 0-1 noise value (unluckily?) low: test again?"
        );
        $this->assertLessThanOrEqual(
            $mean * (1 + $this->binomialZeroOneDelta),
            $value / $trials,
            "Binomial 0-1 noise value (unluckily?) high: test again?"
        );
    }

    /**
     * Test: TestRig\Services\Maths::testBinomialNoise().
     */
    public function testBinomialNoise()
    {
        $n = 500;
        $p = 0.9;
        $trials = 1000;

        // Ensure we get an integer back.
        $value = Maths::binomialNoise($n, $p);
        $this->assertTrue(is_int($value));

        // We already have one value, so get 999 more.
        for ($i = 0; $i < $trials; $i++)
        {
            $value += Maths::binomialNoise($n, $p);
        }

        $this->assertGreaterThanOrEqual(
            $n * $p * (1 - $this->binomialDelta), 
            $value / $trials,
            "Binomial noise value (unluckily?) low: test again?"
        );
        $this->assertLessThanOrEqual(
            $n * $p * (1 + $this->binomialDelta), 
            $value / $trials,
            "Binomial noise value (unluckily?) high: test again?"
        );
    }
}
