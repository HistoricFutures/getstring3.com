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

// Routing.
$routes = array(
    array('/',            'get',  'TestRig\\Controllers\\IndexController::get'),
    array('/data',        'get',  'TestRig\\Controllers\\DataController::index'),
    array('/data/new',    'get',  'TestRig\\Controllers\\DataController::createForm'),
    array('/data/create', 'post', 'TestRig\\Controllers\\DataController::createSubmit'),
);

foreach ($routes as $route)
{
    $app->{$route[1]}($route[0], $route[2]);
}

// Run the Silex app.
$app->run();
