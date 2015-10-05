<?php

/**
 * @file
 * Configured Silex app.
 */

namespace TestRig\Core;

use Silex\Application;

/**
 * @class
 * ConfiguredSilex.
 *
 */
class ConfiguredSilex extends Application
{
    // Last configured app.
    private static $lastApp = null;

    /**
     * Overrides ::__construct().
     *
     * @param string $rootDir = getcwd()
     *   Location of web root.
     * @param array $values = []
     *   As per parent.
     */
    public function __construct($rootDir = null, array $values = [])
    {
        // Need a web root.
        if ($rootDir === null) {
            $rootDir = getcwd();
        }

        // Retrieve environment variables using Dotenv.
        $dotenv = new \Dotenv\Dotenv("$rootDir/..");
        $dotenv->load();

        // Call parent to create app.
        parent::__construct($values);

        // Configure app, potentially in debug mode.
        $env_debug = getenv('DEBUG');
        $this['debug'] = isset($env_debug) && $env_debug;

        // Twig templating via $this['twig'].
        $this->register(new \Silex\Provider\TwigServiceProvider(), array(
            'twig.path' => "$rootDir/views",
        ));

        // Translator provider: we don't need it, but form rendering does.
        $this->register(new \Silex\Provider\TranslationServiceProvider());

        // Form handling.
        $this->register(new \Silex\Provider\FormServiceProvider());

        // Routing.
        $routes = array(
            array('/', 'get', 'TestRig\\Controllers\\IndexController::get'),

            array('/data', 'get', 'TestRig\\Controllers\\DataController::index'),
            array('/data/new', 'match', 'TestRig\\Controllers\\DataController::create'),
            array('/data/{path}', 'get', 'TestRig\\Controllers\\DataController::read'),
            array('/data/{path}/delete', 'match', 'TestRig\\Controllers\\DataController::delete'),

            array('/algo', 'get', 'TestRig\\Controllers\\AlgorithmController::index'),
            array('/algo/new', 'match', 'TestRig\\Controllers\\AlgorithmController::create'),
            array('/algo/{path}', 'get', 'TestRig\\Controllers\\AlgorithmController::read'),
            array('/algo/{path}/run', 'match', 'TestRig\\Controllers\\AlgorithmController::run'),
            array('/algo/{path}/delete', 'match', 'TestRig\\Controllers\\AlgorithmController::delete'),
        );
        foreach ($routes as $route) {
            $this->{$route[1]}($route[0], $route[2]);
        }

        // Set last configured app so we can retrieve it elsewhere.
        self::$lastApp = $this;
    }

    /**
     * Class method to get the most recently configured app.
     *
     * @return ConfiguredSilex
     */
    public static function getLastApp()
    {
        return self::$lastApp;
    }
}
