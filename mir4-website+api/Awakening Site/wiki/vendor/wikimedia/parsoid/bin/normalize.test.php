<?php

// PORT-FIXME: Incomplete. Doesn't support CLI flags of the JS version

require_once __DIR__ . '/../vendor/autoload.php';

use Wikimedia\Parsoid\Html2Wt\DOMNormalizer;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;

$html = file_get_contents( $argv[1] );

$mockEnv = new MockEnv( [] );
$mockState = (object)[
	"env" => $mockEnv,
	"selserMode" => true
];

$doc = ContentUtils::createAndLoadDocument( $html, [ "markNew" => true ] );
$body = DOMCompat::getBody( $doc );

$norm = new DOMNormalizer( $mockState );
$norm->normalize( $body );

$opts = [
	'keepTmp' => true,
	'storeDiffMark' => true,
];
print ContentUtils::dumpDOM( $body, 'DOM post-normalization', $opts );
print "\n";
