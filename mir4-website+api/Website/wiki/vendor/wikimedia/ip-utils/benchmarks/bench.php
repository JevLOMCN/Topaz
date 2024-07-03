<?php

require_once dirname( __DIR__ ) . '/src/IPSet.php';
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

use Wikimedia\IPSet;

$t = microtime( true );
$ipsetPHP = new IPSet( require __DIR__ . '/trusted-hosts.php' );
$t = ( microtime( true ) - $t ) * 1000;
echo "Load from PHP array and initialise: {$t}s\n";

checkIPs( $ipsetPHP );

echo "\n";

$t = microtime( true );
$ipsetJSON = IPSet::newFromJson( file_get_contents( __DIR__ . '/trusted-hosts.json' ) );
$t = ( microtime( true ) - $t ) * 1000;
echo "Load from serialized json and initialise: {$t}s\n";

checkIPs( $ipsetJSON );

function checkIPs( IPSet $ipset ): void {
	foreach ( [ '69.63.176.1' => true, '127.0.0.1' => false ] as $ip => $exists ) {
		if ( $ipset->match( $ip ) === $exists ) {
			echo "IPSet returned correct result for $ip\n";
		}
	}
}
