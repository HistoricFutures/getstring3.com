<?php

/**
 * @file
 * Web index.
 */

// Bootstrap enviromnent including .env file.
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = new Dotenv\Dotenv(__DIR__ . '/..');
$dotenv->load();

// Start app, potentially in debug mode.
$app = new Silex\Application();
$env_debug = getenv('DEBUG');
$app['debug'] = isset($env_debug) && $env_debug;

// Twig templating via $app['twig'].
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/views',
));

// Translator provider: we don't need it, but form rendering does.
$app->register(new Silex\Provider\TranslationServiceProvider());

// Form handling.
$app->register(new Silex\Provider\FormServiceProvider());

// Routing.
$routes = array(
    array('/',            'get',  'TestRig\\Controllers\\IndexController::get'),
    array('/data',        'get',  'TestRig\\Controllers\\DataController::index'),
    array('/data/new',    'match', 'TestRig\\Controllers\\DataController::create'),
    array('/data/{path}', 'get',  'TestRig\\Controllers\\DataController::read'),
    array('/data/{path}/delete', 'match',  'TestRig\\Controllers\\DataController::delete'),
);
foreach ($routes as $route)
{
    $app->{$route[1]}($route[0], $route[2]);
}

// Run the Silex app.
$app->run();
