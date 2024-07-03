<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "after body" insertion mode
 */
class AfterBody extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		[ $part1, $part2 ] = $this->splitInitialMatch(
			true, "\t\n\f\r ", $text, $start, $length, $sourceStart, $sourceLength );
		[ $start, $length, $sourceStart, $sourceLength ] = $part1;
		if ( $length ) {
			$this->dispatcher->inBody->characters(
				$text, $start, $length, $sourceStart, $sourceLength );
		}
		[ $start, $length, $sourceStart, $sourceLength ] = $part2;
		$this->builder->error( "unexpected non-whitespace character after body",
			$sourceStart );
		$this->dispatcher->switchMode( Dispatcher::IN_BODY )
			->characters( $text, $start, $length, $sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'html':
				$dispatcher->inBody->startTag(
					$name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			default:
				$builder->error( "unexpected start tag after body", $sourceStart );
				$dispatcher->switchMode( Dispatcher::IN_BODY )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'html':
				if ( $builder->isFragment ) {
					$builder->error( "unexpected </html> in fragment", $sourceStart );
					return;
				}
				$dispatcher->switchMode( Dispatcher::AFTER_AFTER_BODY );
				break;

			default:
				$builder->error( "unexpected end tag after body", $sourceStart );
				$dispatcher->switchMode( Dispatcher::IN_BODY )
					->endTag( $name, $sourceStart, $sourceLength );
		}
	}

	public function endDocument( $pos ) {
		$this->builder->stopParsing( $pos );
	}

	public function comment( $text, $sourceStart, $sourceLength ) {
		$this->builder->comment( [ TreeBuilder::UNDER, $this->builder->stack->item( 0 ) ],
			$text, $sourceStart, $sourceLength );
	}
}
