<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "in head noscript" insertion mode
 */
class InHeadNoscript extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;

		// Insert whitespace
		[ $part1, $part2 ] = $this->splitInitialMatch( true, "\t\n\f\r ",
			$text, $start, $length, $sourceStart, $sourceLength );
		[ $start, $length, $sourceStart, $sourceLength ] = $part1;
		if ( $length ) {
			$dispatcher->inHead->characters(
				$text, $start, $length, $sourceStart, $sourceLength );
		}

		// Switch mode on non-whitespace
		[ $start, $length, $sourceStart, $sourceLength ] = $part2;
		if ( $length ) {
			$builder->error( "unexpected non-whitespace character in head in noscript, " .
				"closing noscript", $sourceStart );
			$builder->pop( $sourceStart, 0 );
			$dispatcher->switchMode( Dispatcher::IN_HEAD )
				->characters( $text, $start, $length, $sourceStart, $sourceLength );
		}
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'html':
				$dispatcher->inBody->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'basefont':
			case 'bgsound':
			case 'link':
			case 'meta':
			case 'noframes':
			case 'style':
				$dispatcher->inHead->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'head':
			case 'noscript':
				$builder->error( "unexpected <$name> in head in noscript, ignoring", $sourceStart );
				return;

			default:
				$builder->error( "unexpected <$name> in head in noscript, closing noscript",
					$sourceStart );
				$builder->pop( $sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_HEAD )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'noscript':
				$builder->pop( $sourceStart, $sourceLength );
				$dispatcher->switchMode( Dispatcher::IN_HEAD );
				break;

			case 'br':
				$builder->error( "unexpected </br> in head in noscript, closing noscript",
					$sourceStart );
				$builder->pop( $sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_HEAD )
					->endTag( $name, $sourceStart, $sourceLength );
				break;

			default:
				$builder->error( "unexpected </$name> in head in noscript, ignoring",
					$sourceStart );
				return;
		}
	}

	public function endDocument( $pos ) {
		$this->builder->error( "unexpected end-of-file in head in noscript", $pos );
		$this->builder->pop( $pos, 0 );
		$this->dispatcher->switchMode( Dispatcher::IN_HEAD )
			->endDocument( $pos );
	}
}
