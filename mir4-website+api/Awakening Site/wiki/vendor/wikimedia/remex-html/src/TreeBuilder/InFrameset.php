<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "in frameset" insertion mode
 */
class InFrameset extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->handleFramesetWhitespace( false, $text, $start, $length, $sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'html':
				$dispatcher->inBody->startTag(
					$name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'frameset':
				$builder->insertElement( $name, $attrs, false, $sourceStart, $sourceLength );
				break;

			case 'frame':
				$dispatcher->ack = true;
				$builder->insertElement( $name, $attrs, true, $sourceStart, $sourceLength );
				break;

			case 'noframes':
				$dispatcher->inHead->startTag(
					$name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			default:
				$builder->error( "unexpected start tag in frameset, ignoring", $sourceStart );
				return;
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'frameset':
				if ( $stack->current->htmlName === 'html' ) {
					$builder->error( "unexpected </frameset> in fragment context", $sourceStart );
					return;
				}
				$builder->pop( $sourceStart, $sourceLength );
				if ( !$builder->isFragment && $stack->current !== 'frameset' ) {
					$dispatcher->switchMode( Dispatcher::AFTER_FRAMESET );
				}
				break;

			default:
				$builder->error( "unexpected </$name> in frameset", $sourceStart );
				return;
		}
	}

	public function endDocument( $pos ) {
		$builder = $this->builder;
		$stack = $builder->stack;

		if ( $stack->current->htmlName !== 'html' ) {
			$builder->error( "unexpected end of file in frameset mode", $pos );
		}

		$builder->stopParsing( $pos );
	}
}
