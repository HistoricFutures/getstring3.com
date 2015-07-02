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

// Routing.
$app->get('/', 'TestRig\\Controllers\\IndexController::get');

// Run the Silex app.
$app->run();
