<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\Tokenizer\PlainAttributes;

/**
 * The "after head" insertion mode
 */
class AfterHead extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;

		// Insert whitespace
		[ $part1, $part2 ] = $this->splitInitialMatch(
			true, "\t\n\f\r ", $text, $start, $length, $sourceStart, $sourceLength );
		[ $start, $length, $sourceStart, $sourceLength ] = $part1;
		if ( $length ) {
			$builder->insertCharacters( $text, $start, $length,
				$sourceStart, $sourceLength );
		}

		// Switch mode on non-whitespace
		[ $start, $length, $sourceStart, $sourceLength ] = $part2;
		if ( $length ) {
			$builder->insertElement( 'body', new PlainAttributes, false, $sourceStart, 0 );
			$dispatcher->switchMode( Dispatcher::IN_BODY )
				->characters( $text, $start, $length, $sourceStart, $sourceLength );
		}
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'html':
				$dispatcher->inBody->startTag( $name, $attrs, $selfClose,
					$sourceStart, $sourceLength );
				break;

			case 'body':
				$builder->insertElement( $name, $attrs, false, $sourceStart, $sourceLength );
				$builder->framesetOK = false;
				$dispatcher->switchMode( Dispatcher::IN_BODY );
				break;

			case 'frameset':
				$builder->insertElement( $name, $attrs, false, $sourceStart, $sourceLength );
				$dispatcher->switchMode( Dispatcher::IN_FRAMESET );
				break;

			case 'base':
			case 'basefont':
			case 'bgsound':
			case 'link':
			case 'meta':
			case 'noframes':
			case 'script':
			case 'style':
			case 'template':
			case 'title':
				$builder->error( "unexpected <$name> after </head>, accepting", $sourceStart );
				$stack->push( $builder->headElement );
				$dispatcher->inHead->startTag(
					$name, $attrs, $selfClose, $sourceStart, $sourceLength );
				$stack->remove( $builder->headElement );
				break;

			case 'head':
				$builder->error( "unexpected <head> after </head>, ignoring", $sourceStart );
				return;

			default:
				$builder->insertElement( 'body', new PlainAttributes, false, $sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_BODY )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'template':
				$dispatcher->inHead->endTag( $name, $sourceStart, $sourceLength );
				break;

			case 'body':
			case 'html':
			case 'br':
				$builder->insertElement( 'body', new PlainAttributes, false, $sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_BODY )
					->endTag( $name, $sourceStart, $sourceLength );
				break;

			default:
				$builder->error( "unexpected </$name> after head, ignoring", $sourceStart );
				return;
		}
	}

	public function endDocument( $pos ) {
		$this->builder->insertElement( 'body', new PlainAttributes, false, $pos, 0 );
		$this->dispatcher->switchMode( Dispatcher::IN_BODY )
			->endDocument( $pos );
	}
}
