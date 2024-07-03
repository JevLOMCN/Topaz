#!/usr/bin/env php
<?php

if ( PHP_SAPI !== 'cli' ) {
	exit;
}

require __DIR__ . '/../vendor/autoload.php';

use Wikimedia\RemexHtml\DOM;
use Wikimedia\RemexHtml\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder;
use Wikimedia\RemexHtml\Serializer;

function reserialize( $text ) {
	$handler = new Tokenizer\TokenSerializer;
	$tokenizer = new Tokenizer\Tokenizer( $handler, $text, [] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
	print $handler->getOutput() . "\n";
	foreach ( $handler->getErrors() as $error ) {
		print "Error at {$error[1]}: {$error[0]}\n";
	}
}

function reserializeState( $text, $state, $endTag ) {
	$handler = new Tokenizer\TokenSerializer;
	$tokenizer = new Tokenizer\Tokenizer( $handler, $text, [] );
	$tokenizer->execute( [ 'state' => $state, 'appropriateEndTag' => $endTag ] );
	print $handler->getOutput() . "\n";
	foreach ( $handler->getErrors() as $error ) {
		print "Error at {$error[1]}: {$error[0]}\n";
	}
}

function reserializeScript( $text ) {
	reserializeState( $text, Tokenizer\Tokenizer::STATE_SCRIPT_DATA, 'script' );
}

function reserializeXmp( $text ) {
	reserializeState( $text, Tokenizer\Tokenizer::STATE_RCDATA, 'xmp' );
}

function trace( $text ) {
	$traceCallback = function ( $msg ) {
		print "$msg\n";
	};
	$formatter = new Serializer\HtmlFormatter;
	$serializer = new Serializer\Serializer( $formatter );
	$treeTracer = new TreeBuilder\TreeMutationTracer( $serializer, $traceCallback );
	$treeBuilder = new TreeBuilder\TreeBuilder( $treeTracer, [] );
	$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
	$dispatchTracer = new TreeBuilder\DispatchTracer( $text, $dispatcher, $traceCallback );
	$tokenizer = new Tokenizer\Tokenizer( $dispatchTracer, $text, [] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );

	print $serializer->getResult() . "\n";
}

function traceDestruct( $text ) {
	$traceCallback = function ( $msg ) {
		print "$msg\n";
	};
	$destructTracer = new TreeBuilder\DestructTracer( $traceCallback );
	$treeTracer = new TreeBuilder\TreeMutationTracer( $destructTracer, $traceCallback );
	$treeBuilder = new TreeBuilder\TreeBuilder( $treeTracer, [] );
	$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
	$dispatchTracer = new TreeBuilder\DispatchTracer( $text, $dispatcher, $traceCallback );
	$tokenizer = new Tokenizer\Tokenizer( $dispatchTracer, $text, [] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
}

function tidy( $text ) {
	$error = function ( $msg, $pos ) {
		print "  *  [$pos] $msg\n";
	};
	$formatter = new Serializer\HtmlFormatter;
	$serializer = new Serializer\Serializer( $formatter, $error );
	$treeBuilder = new TreeBuilder\TreeBuilder( $serializer, [] );
	$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
	$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, $GLOBALS['tokenizerOptions'] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
	print $serializer->getResult() . "\n";
}

function test( $text ) {
	$error = function ( $msg, $pos ) {
		print "  *  [$pos] $msg\n";
	};
	$formatter = new Serializer\TestFormatter;
	$serializer = new Serializer\Serializer( $formatter, $error );
	$treeBuilder = new TreeBuilder\TreeBuilder( $serializer, [] );
	$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
	$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, $GLOBALS['tokenizerOptions'] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
	print $serializer->getResult() . "\n";
}

function tidyViaDOM( $text ) {
	$error = function ( $msg, $pos ) {
		print "  *  [$pos] $msg\n";
	};
	$formatter = new Serializer\HtmlFormatter;
	$domBuilder = new DOM\DOMBuilder( [ 'errorCallback' => $error ] );
	$serializer = new DOM\DOMSerializer( $domBuilder, $formatter );
	$treeBuilder = new TreeBuilder\TreeBuilder( $serializer, [] );
	$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
	$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, [] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
	print $serializer->getResult() . "\n";
}

function testViaDOM( $text ) {
	$error = function ( $msg, $pos ) {
		print "  *  [$pos] $msg\n";
	};
	$formatter = new Serializer\TestFormatter;
	$domBuilder = new DOM\DOMBuilder( [ 'errorCallback' => $error ] );
	$serializer = new DOM\DOMSerializer( $domBuilder, $formatter );
	$treeBuilder = new TreeBuilder\TreeBuilder( $serializer, [] );
	$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
	$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, [] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
	print $serializer->getResult() . "\n";
}

function benchmarkNull( $text ) {
	$time = -microtime( true );
	$handler = new Tokenizer\NullTokenHandler;
	$tokenizer = new Tokenizer\Tokenizer( $handler, $text, $GLOBALS['tokenizerOptions'] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
	$time += microtime( true );
	print "$time\n";
}

function benchmarkSerialize( $text ) {
	$time = -microtime( true );
	$handler = new Tokenizer\TokenSerializer;
	$tokenizer = new Tokenizer\Tokenizer( $handler, $text, $GLOBALS['tokenizerOptions'] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
	$time += microtime( true );
	print "$time\n";
}

function benchmarkTreeBuilder( $text ) {
	$time = -microtime( true );
	$handler = new TreeBuilder\NullTreeHandler;
	$treeBuilder = new TreeBuilder\TreeBuilder( $handler, [] );
	$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
	$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, $GLOBALS['tokenizerOptions'] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
	$time += microtime( true );
	print "$time\n";
}

function benchmarkDOM( $text ) {
	$time = -microtime( true );
	$domBuilder = new DOM\DOMBuilder;
	$treeBuilder = new TreeBuilder\TreeBuilder( $domBuilder, [ 'ignoreErrors' => true ] );
	$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
	$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, $GLOBALS['tokenizerOptions'] );
	$tokenizer->execute( $GLOBALS['executeOptions'] );
	$time += microtime( true );
	print "$time\n";
}

function benchmarkTidyFast( $text ) {
	$n = 100;
	$time = -microtime( true );
	for ( $i = 0; $i < $n; $i++ ) {
		$formatter = new Serializer\FastFormatter;
		$serializer = new Serializer\Serializer( $formatter );
		$treeBuilder = new TreeBuilder\TreeBuilder( $serializer, [] );
		$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, $GLOBALS['tokenizerOptions'] );
		$tokenizer->execute( $GLOBALS['executeOptions'] );
	}
	$time += microtime( true );
	print ( $time / $n ) . "\n";
}

function benchmarkTidySlow( $text ) {
	$n = 100;
	$time = -microtime( true );
	for ( $i = 0; $i < $n; $i++ ) {
		$formatter = new Serializer\HtmlFormatter;
		$serializer = new Serializer\Serializer( $formatter );
		$treeBuilder = new TreeBuilder\TreeBuilder( $serializer, [] );
		$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, [] );
		$tokenizer->execute( $GLOBALS['executeOptions'] );
	}
	$time += microtime( true );
	print ( $time / $n ) . "\n";
}

function generate( $text ) {
	$generator = Tokenizer\TokenGenerator::generate( $text, $GLOBALS['tokenizerOptions'] );
	foreach ( $generator as $token ) {
		if ( $token['type'] === 'text' ) {
			$token['text'] = substr( $token['text'], $token['start'], $token['length'] );
			unset( $token['start'] );
			unset( $token['length'] );
		}
		print_r( $token );
	}
}

function benchmarkGenerate( $text ) {
	$time = -microtime( true );
	$generator = Tokenizer\TokenGenerator::generate( $text, $GLOBALS['tokenizerOptions'] );
	foreach ( $generator as $token ) {
	}
	$time += microtime( true );
	print "$time\n";
}

$tokenizerOptions = [
	'ignoreNulls' => true,
	'ignoreCharRefs' => true,
	'ignoreErrors' => true,
	'skipPreprocess' => true,
];
$executeOptions = [
	// 'fragmentNamespace' => \RemexHtml\HTMLData::NS_HTML,
	// 'fragmentName' => 'div'
];
$text = file_exists( '/tmp/Australia.html') ?
	file_get_contents( '/tmp/Australia.html' ) : '';

while ( ( $__line = readline( "> " ) ) !== false ) {
	readline_add_history( $__line );
	$__val = eval( $__line . ";" );
}

