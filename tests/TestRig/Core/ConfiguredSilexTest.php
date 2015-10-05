<?php

/**
 * @file
 * Test: TestRig\Core\ConfiguredSilex.
 */

namespace Tests\Services;

use TestRig\Core\ConfiguredSilex;
use Tests\AbstractTestCase;

/**
 * @class
 * Test: TestRig\Services\ConfiguredSilex.
 */
class ConfiguredSilexTest extends AbstractTestCase
{
    /**
     * Test: TestRig\Services\ConfiguredSilex::__construct().
     */
    public function testConstruct()
    {
        $app = new ConfiguredSilex(__DIR__ . '/../..');

        try {
            $app['twig'];
            $this->fail("Twig service did not try to find its views folder.");
        }
        catch (\Twig_Error_Loader $e) {
        }
    }

    /**
     * Test: TestRig\Services\ConfiguredSilex::getLastApp().
     */
    public function testGetLastApp()
    {
        $app = new ConfiguredSilex(__DIR__ . '/../..');
        $lastApp = ConfiguredSilex::getLastApp();
        $this->assertEquals($app, $lastApp);
    }
}
