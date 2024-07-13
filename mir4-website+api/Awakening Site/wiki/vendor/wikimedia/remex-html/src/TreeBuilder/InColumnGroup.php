<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "in column group" insertion mode
 */
class InColumnGroup extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		[ $part1, $part2 ] = $this->splitInitialMatch(
			true, "\t\n\f\r ", $text, $start, $length, $sourceStart, $sourceLength );

		[ $start, $length, $sourceStart, $sourceLength ] = $part1;
		if ( $length !== 0 ) {
			$this->builder->insertCharacters( $text, $start, $length,
				$sourceStart, $sourceLength );
		}

		[ $start, $length, $sourceStart, $sourceLength ] = $part2;

		if ( $length === 0 ) {
			// All done with this sequence
			return;
		}

		// Handle non-whitespace as "anything else"
		$builder = $this->builder;
		$stack = $builder->stack;
		if ( $stack->current->htmlName !== 'colgroup' ) {
			$builder->error( 'text should close the colgroup but another element is open',
				$sourceStart );
			// Ignore
			return;
		} else {
			$builder->pop( $sourceStart, 0 );
		}
		$this->dispatcher->switchMode( Dispatcher::IN_TABLE )
			->characters( $text, $start, $length, $sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$dispatcher = $this->dispatcher;
		$builder = $this->builder;
		$stack = $builder->stack;

		switch ( $name ) {
			case 'html':
				$dispatcher->inBody->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'col':
				$dispatcher->ack = true;
				$builder->insertElement( $name, $attrs, true, $sourceStart, $sourceLength );
				break;

			case 'template':
				$dispatcher->inHead->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			default:
				if ( $stack->current->htmlName !== 'colgroup' ) {
					$builder->error( 'start tag should close the colgroup but another element is open',
						$sourceStart );
					// Ignore
					return;
				} else {
					$builder->pop( $sourceStart, 0 );
				}
				$this->dispatcher->switchMode( Dispatcher::IN_TABLE )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$dispatcher = $this->dispatcher;
		$builder = $this->builder;
		$stack = $builder->stack;

		switch ( $name ) {
			case 'colgroup':
				if ( $stack->current->htmlName !== 'colgroup' ) {
					$builder->error( '</colgroup> found but another element is open, ignoring',
						$sourceStart );
					return;
				}
				$builder->pop( $sourceStart, $sourceLength );
				$dispatcher->switchMode( Dispatcher::IN_TABLE );
				break;

			case 'col':
				$builder->error( '</col> found in column group mode, ignoring', $sourceStart );
				break;

			case 'template':
				$dispatcher->inHead->endTag( $name, $sourceStart, $sourceLength );
				break;

			default:
				if ( $stack->current->htmlName !== 'colgroup' ) {
					$builder->error( 'non-matching end tag should close the colgroup ' .
						' but another element is open', $sourceStart );
					// Ignore
					return;
				} else {
					$builder->pop( $sourceStart, 0 );
				}
				$dispatcher->switchMode( Dispatcher::IN_TABLE )
					->endTag( $name, $sourceStart, $sourceLength );
		}
	}

	public function endDocument( $pos ) {
		$this->dispatcher->inBody->endDocument( $pos );
	}
}
