<?php

namespace Wikimedia\RemexHtml\Serializer;

use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\TreeBuilder\Element;
use Wikimedia\RemexHtml\TreeBuilder\TraceFormatter;

class SerializerWithTracer extends Serializer {
	private $traceCallback;
	private $verbosity;

	public function __construct( Formatter $formatter, $errorCallback = null, $traceCallback = null,
		$verbosity = 0
	) {
		$this->traceCallback = $traceCallback;
		$this->verbosity = $verbosity;
		parent::__construct( $formatter, $errorCallback );
	}

	private function handle( $funcName, $args ) {
		$this->trace( call_user_func_array( [ TraceFormatter::class, $funcName ], $args ) );
		call_user_func_array( [ parent::class, $funcName ], $args );
		if ( $this->verbosity > 0 && $funcName !== 'endDocument' ) {
			$this->trace( "Dump after $funcName: " . $this->dump() );
		}
	}

	private function trace( $msg ) {
		call_user_func( $this->traceCallback, "[Serializer] $msg" );
	}

	public function startDocument( $fragmentNamespace, $fragmentName ) {
		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function endDocument( $pos ) {
		if ( count( $this->nodes ) ) {
			$nodeTags = '';
			foreach ( $this->nodes as $node ) {
				if ( $nodeTags !== '' ) {
					$nodeTags .= ', ';
				}
				$nodeTags .= $node->getDebugTag();
			}
			$this->trace( "endDocument: unclosed elements: $nodeTags" );
		} else {
			$this->trace( "endDocument: no unclosed elements" );
		}

		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function characters( $preposition, $refElement, $text, $start, $length,
		$sourceStart, $sourceLength
	) {
		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function insertElement( $preposition, $refElement, Element $element, $void,
		$sourceStart, $sourceLength
	) {
		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function endTag( Element $element, $sourceStart, $sourceLength ) {
		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function comment( $preposition, $refElement, $text, $sourceStart, $sourceLength ) {
		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function error( $text, $pos ) {
		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function mergeAttributes( Element $element, Attributes $attrs, $sourceStart ) {
		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function removeNode( Element $element, $sourceStart ) {
		$this->handle( __FUNCTION__, func_get_args() );
	}

	public function reparentChildren( Element $element, Element $newParent, $sourceStart ) {
		$this->handle( __FUNCTION__, func_get_args() );
	}
}
