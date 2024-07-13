#!/usr/bin/env php
<?php

/**
 * Generate pseudorandom input, and run it in both Remex and Html5Depurate.
 * Flag any cases where the results differ.
 */

use Wikimedia\RemexHtml\Tools\FuzzTest;

if ( PHP_SAPI !== 'cli' ) {
	exit;
}

require __DIR__ . '/../vendor/autoload.php';

if ( !isset( $argv[2] ) ) {
	echo "Usage: fuzz.php <length> <url>\n";
	exit( 1 );
}

$fuzzTest = new FuzzTest\FuzzTest( $argv[1], $argv[2] );
$fuzzTest->execute();
