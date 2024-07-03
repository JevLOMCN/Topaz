<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\Tokenizer\PlainAttributes;

/**
 * The "before head" insertion mode
 */
class BeforeHead extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		// Ignore whitespace
		[ $part1, $part2 ] = $this->splitInitialMatch(
			true, "\t\n\f\r ", $text, $start, $length, $sourceStart, $sourceLength );
		[ $start, $length, $sourceStart, $sourceLength ] = $part2;
		if ( !$length ) {
			return;
		}
		// Handle non-whitespace
		$this->builder->headElement = $this->builder->insertElement(
			'head', new PlainAttributes, false, $sourceStart, 0 );
		$this->dispatcher->switchMode( Dispatcher::IN_HEAD )
			->characters( $text, $start, $length, $sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		if ( $name === 'html' ) {
			$this->dispatcher->inBody->startTag( $name, $attrs, $selfClose,
				$sourceStart, $sourceLength );
		} elseif ( $name === 'head' ) {
			$this->builder->headElement = $this->builder->insertElement(
				$name, $attrs, false, $sourceStart, $sourceLength );
			$this->dispatcher->switchMode( Dispatcher::IN_HEAD );
		} else {
			$this->builder->headElement = $this->builder->insertElement(
				'head', new PlainAttributes, false, $sourceStart, 0 );
			$this->dispatcher->switchMode( Dispatcher::IN_HEAD )
				->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$allowed = [ "head" => true, "body" => true, "html" => true, "br" => true ];
		if ( !isset( $allowed[$name] ) ) {
			$this->builder->error( 'end tag not allowed before head', $sourceStart );
			return;
		}
		$this->builder->headElement = $this->builder->insertElement(
			'head', new PlainAttributes, false, $sourceStart, 0 );
		$this->dispatcher->switchMode( Dispatcher::IN_HEAD )
			->endTag( $name, $sourceStart, $sourceLength );
	}

	public function endDocument( $pos ) {
		$this->builder->headElement = $this->builder->insertElement(
			'head', new PlainAttributes, false, $pos, 0 );
		$this->dispatcher->switchMode( Dispatcher::IN_HEAD )
			->endDocument( $pos );
	}
}
