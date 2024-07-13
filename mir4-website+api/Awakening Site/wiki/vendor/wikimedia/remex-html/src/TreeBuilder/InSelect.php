<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "in select" insertion mode
 */
class InSelect extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->stripNulls(
			function ( $text, $start, $length, $sourceStart, $sourceLength ) {
				$this->builder->insertCharacters( $text, $start, $length,
					$sourceStart, $sourceLength );
			},
			$text, $start, $length, $sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'html':
				$dispatcher->inBody->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'option':
				if ( $stack->current->htmlName === 'option' ) {
					$builder->pop( $sourceStart, 0 );
				}
				$builder->insertElement( 'option', $attrs, false, $sourceStart, $sourceLength );
				break;

			case 'optgroup':
				if ( $stack->current->htmlName === 'option' ) {
					$builder->pop( $sourceStart, 0 );
				}
				if ( $stack->current->htmlName === 'optgroup' ) {
					$builder->pop( $sourceStart, 0 );
				}
				$builder->insertElement( 'optgroup', $attrs, false, $sourceStart, $sourceLength );
				break;

			case 'select':
				if ( !$stack->isInSelectScope( 'select' ) ) {
					$builder->error( "<select> found in select mode but no select element is in " .
						"scope, ignoring", $sourceStart );
					return;
				}

				$builder->error( "<select> found inside a select element", $sourceStart );
				$builder->popAllUpToName( 'select', $sourceStart, $sourceLength );
				$dispatcher->reset();
				break;

			case 'input':
			case 'keygen':
			case 'textarea':
				$builder->error( "<$name> found inside a select element", $sourceStart );
				if ( !$stack->isInSelectScope( 'select' ) ) {
					// Ignore
					return;
				}
				$builder->popAllUpToName( 'select', $sourceStart, 0 );
				$dispatcher->reset()
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'script':
			case 'template':
				$dispatcher->inHead->startTag( $name, $attrs, $selfClose,
					$sourceStart, $sourceLength );
				break;

			default:
				$builder->error( "<$name> found inside a select element, ignoring", $sourceStart );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'optgroup':
				if ( $stack->current->htmlName === 'option' ) {
					$penultimate = $stack->item( $stack->length() - 2 );
					if ( $penultimate && $penultimate->htmlName === 'optgroup' ) {
						$builder->pop( $sourceStart, 0 );
					}
				}
				if ( $stack->current->htmlName !== 'optgroup' ) {
					$builder->error( "unexpected </optgroup>, ignoring", $sourceStart );
					return;
				}
				$builder->pop( $sourceStart, $sourceLength );
				break;

			case 'option':
				if ( $stack->current->htmlName !== 'option' ) {
					$builder->error( "unexpected </option>, ignoring", $sourceStart );
					return;
				}
				$builder->pop( $sourceStart, $sourceLength );
				break;

			case 'select':
				if ( !$stack->isInSelectScope( 'select' ) ) {
					$builder->error( "</select> found but the select element is " .
						"not in scope", $sourceStart );
					return;
				}
				$builder->popAllUpToName( 'select', $sourceStart, $sourceLength );
				$dispatcher->reset();
				break;

			case 'template':
				$dispatcher->inHead->endTag( $name, $sourceStart, $sourceLength );
				break;

			default:
				$builder->error( "unexpected </$name> in select, ignoring", $sourceStart );
				break;
		}
	}

	public function endDocument( $pos ) {
		$this->dispatcher->inBody->endDocument( $pos );
	}
}
