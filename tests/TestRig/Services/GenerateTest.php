<?php

/**
 * @file
 * Test: TestRig\Services\Generate.
 */

use TestRig\Services\Generate;

/**
 * @class
 * Test: TestRig\Services\Generate.
 */

class GenerateTest extends \PHPUnit_Framework_TestCase
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
}
