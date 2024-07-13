<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "in cell" insertion mode
 */
class InCell extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->dispatcher->inBody->characters( $text, $start, $length,
			$sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		switch ( $name ) {
			case 'caption':
			case 'col':
			case 'colgroup':
			case 'tbody':
			case 'td':
			case 'tfoot':
			case 'th':
			case 'thead':
			case 'tr':
				if ( !$this->builder->stack->isInTableScope( 'td' )
					&& !$this->builder->stack->isInTableScope( 'th' )
				) {
					$this->builder->error( "<$name> tag should close the cell but none is in scope",
						$sourceStart );
					return;
				}
				$this->closeTheCell( $sourceStart )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			default:
				$this->dispatcher->inBody->startTag(
					$name, $attrs, $selfClose, $sourceStart, $sourceLength );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'td':
			case 'th':
				if ( !$stack->isInTableScope( $name ) ) {
					$builder->error( "</$name> encountered but there is no $name in scope, ignoring",
						$sourceStart );
					return;
				}
				$builder->generateImpliedEndTags( false, $sourceStart );
				if ( $stack->current->htmlName !== $name ) {
					$builder->error( "</$name> encountered when there are tags open " .
						"which can't be closed automatically", $sourceStart );
				}
				$builder->popAllUpToName( $name, $sourceStart, $sourceLength );
				$builder->afe->clearToMarker();
				$dispatcher->switchMode( Dispatcher::IN_ROW );
				break;

			case 'body':
			case 'caption':
			case 'col':
			case 'colgroup':
			case 'html':
				$builder->error( "unexpected </$name> in cell, ignoring", $sourceStart );
				return;

			case 'table':
			case 'tbody':
			case 'tfoot':
			case 'thead':
			case 'tr':
				if ( !$stack->isInTableScope( $name ) ) {
					$builder->error( "</$name> encountered but there is no $name in scope, ignoring",
						$sourceStart );
					return;
				}
				$this->closeTheCell( $sourceStart )
					->endTag( $name, $sourceStart, $sourceLength );
				break;

			default:
				$dispatcher->inBody->endTag( $name, $sourceStart, $sourceLength );
		}
	}

	public function endDocument( $pos ) {
		$this->dispatcher->inBody->endDocument( $pos );
	}

	private function closeTheCell( $sourceStart ) {
		$tdth = [ 'td' => true, 'th' => true ];
		$builder = $this->builder;
		$stack = $builder->stack;
		$builder->generateImpliedEndTags( false, $sourceStart );
		if ( !isset( $tdth[$stack->current->htmlName] ) ) {
			$builder->error( "closing the cell but there are tags open " .
				"which can't be closed automatically", $sourceStart );
		}
		$builder->afe->clearToMarker();
		return $this->dispatcher->switchMode( Dispatcher::IN_ROW );
	}
}
