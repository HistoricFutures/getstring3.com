#!/usr/bin/env php
<?php

/**
 * @file
 * Create dataset from command line.
 *
 * @param string $filename
 *   Relative path to recipe file.
 * @return string
 *   Location of new dataset.
 * @throws 1
 *   Incorrect usage.
 * @throws 2
 *   Recipe file not found.
 */

// Bootstrap environment including .env file.
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = new Dotenv\Dotenv(__DIR__ . '/..');
$dotenv->load();

// All processed files to go into the web root.
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../web';

// Check command-line usage.
if (!isset($argv[1])) {
  print "Usage: {$argv[0]} <filename>\n";
  exit(1);
}

$app = new TestRig\Core\ConfiguredSilex($_SERVER['DOCUMENT_ROOT']);

// Attempt to create a dataset from this YAML file.
try {
    $dataset = new TestRig\Models\Dataset();
    $datasetDir = $dataset->createFromFilename($argv[1]);
    $fullPath = $dataset->fullPath($datasetDir);
    print "Created: $fullPath\n";
}
catch (TestRig\Exceptions\MissingFileException $e) {
    print "File not found: {$argv[1]}'.\n";
    exit(2);
}
