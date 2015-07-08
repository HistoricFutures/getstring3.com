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

    /**
     * Test: TestRig\Services\Maths::testPoissonianNoise().
     */
    public function testPoissonianNoise()
    {
        $mean = 50;
        $number = 1000;

        // Super-difficult to test as it's not very narrow.
        // Test integeriness, then get lots of values and test average.
        $value = Maths::poissonianNoise($mean);
        $this->assertTrue(is_int($value));

        // We already have one value, so get 999 more.
        for ($i = 0; $i < $number; $i++)
        {
            $value += Maths::poissonianNoise($mean);
        }
        $this->assertGreaterThanOrEqual(
            $mean * (1 - $this->poissonianDelta),
            $value / $number,
            "Poissonian noise value (unluckily?) low: test again?"
        );
        $this->assertLessThanOrEqual(
            $mean * (1 + $this->poissonianDelta),
            $value / $number,
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
}
