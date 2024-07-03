<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "in row" insertion mode
 */
class InRow extends InsertionMode {
	private static $tableRowContext = [
		'tr' => true,
		'template' => true,
		'html' => true,
	];

	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->dispatcher->inTable->characters( $text, $start, $length,
			$sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'th':
			case 'td':
				$builder->clearStackBack( self::$tableRowContext, $sourceStart );
				$builder->insertElement( $name, $attrs, false, $sourceStart, $sourceLength );
				$dispatcher->switchMode( Dispatcher::IN_CELL );
				$builder->afe->insertMarker();
				break;

			case 'caption':
			case 'col':
			case 'colgroup':
			case 'tbody':
			case 'tfoot':
			case 'thead':
			case 'tr':
				if ( !$stack->isInTableScope( 'tr' ) ) {
					$builder->error( "<$name> should close the tr but it is not in scope",
						$sourceStart );
					// Ignore
					return;
				}
				$builder->clearStackBack( self::$tableRowContext, $sourceStart );
				$builder->pop( $sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_TABLE_BODY )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			default:
				$dispatcher->inTable->startTag( $name, $attrs, $selfClose,
					$sourceStart, $sourceLength );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'tr':
				if ( !$stack->isInTableScope( 'tr' ) ) {
					$builder->error( '</tr> found but no tr element in scope', $sourceStart );
					// Ignore
					return;
				}
				$builder->clearStackBack( self::$tableRowContext, $sourceStart );
				$builder->pop( $sourceStart, $sourceLength );
				$dispatcher->switchMode( Dispatcher::IN_TABLE_BODY );
				break;

			case 'table':
				if ( !$stack->isInTableScope( 'tr' ) ) {
					$builder->error( "</table> should close the tr but it is not in scope",
						$sourceStart );
					// Ignore
					return;
				}
				$builder->clearStackBack( self::$tableRowContext, $sourceStart );
				$builder->pop( $sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_TABLE_BODY )
					->endTag( $name, $sourceStart, $sourceLength );
				break;

			case 'tbody':
			case 'tfoot':
			case 'thead':
				if ( !$stack->isInTableScope( $name ) ) {
					$builder->error( "</$name> encountered but there is no $name element in scope",
						$sourceStart );
					return;
				}
				if ( !$stack->isInTableScope( 'tr' ) ) {
					return;
				}
				$builder->clearStackBack( self::$tableRowContext, $sourceStart );
				$builder->pop( $sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_TABLE_BODY )
					->endTag( $name, $sourceStart, $sourceLength );
				break;

			case 'body':
			case 'caption':
			case 'col':
			case 'colgroup':
			case 'html':
			case 'td':
			case 'th':
				$builder->error( "</$name> encountered in row mode, ignoring", $sourceStart );
				return;

			default:
				$dispatcher->inTable->endTag( $name, $sourceStart, $sourceLength );
		}
	}

	public function endDocument( $pos ) {
		$this->dispatcher->inTable->endDocument( $pos );
	}
}
