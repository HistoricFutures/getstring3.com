<?php

/**
 * @file
 * Web index.
 */

// Bootstrap enviromnent including .env file.
require_once __DIR__ . '/../vendor/autoload.php';

$app = new TestRig\Core\ConfiguredSilex(__DIR__);

// Run the Silex app.
$app->run();
