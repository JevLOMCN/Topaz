<?php

// PORT-FIXME: Incomplete. Doesn't support CLI flags of the JS version

require_once __DIR__ . '/../vendor/autoload.php';

use Wikimedia\Parsoid\Html2Wt\DOMDiff;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;

$html1 = file_get_contents( $argv[1] );
$html2 = file_get_contents( $argv[2] );

$mockEnv = new MockEnv( [] );

$doc1 = ContentUtils::createAndLoadDocument( $html1, [ "markNew" => true ] );
$doc2 = ContentUtils::createAndLoadDocument( $html2, [ "markNew" => true ] );

$body1 = DOMCompat::getBody( $doc1 );
$body2 = DOMCompat::getBody( $doc2 );

$domDiff = new DOMDiff( $mockEnv );
$domDiff->diff( $body1, $body2 );

$opts = [
	'keepTmp' => true,
	'storeDiffMark' => true,
	'quiet' => true
];
print ContentUtils::dumpDOM( $body2, 'DIFF-marked DOM', $opts );
