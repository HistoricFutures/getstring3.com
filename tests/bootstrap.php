<?php

/**
 * @file
 * Bootstrap for tests.
 */

// Call Dotenv on the root ~/.env; some variables can then be
// overridden in phpunit.xml.dist's <php> tag.
$dotenv = new Dotenv\Dotenv(__DIR__ . '/..');
$dotenv->load();
