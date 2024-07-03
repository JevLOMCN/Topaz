<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\Tokenizer\PlainAttributes;

/**
 * The "in table body" insertion mode
 */
class InTableBody extends InsertionMode {
	private static $tableBodyContext = [
		'tbody' => true,
		'tfoot' => true,
		'thead' => true,
		'template' => true,
		'html' => true
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
			case 'tr':
				$builder->clearStackBack( self::$tableBodyContext, $sourceStart );
				$builder->insertElement( $name, $attrs, false, $sourceStart, $sourceLength );
				$dispatcher->switchMode( Dispatcher::IN_ROW );
				break;

			case 'th':
			case 'td':
				$builder->error( "<$name> encountered in table body (not row) mode", $sourceStart );
				$builder->clearStackBack( self::$tableBodyContext, $sourceStart );
				$builder->insertElement( 'tr', new PlainAttributes, false, $sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_ROW )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'caption':
			case 'col':
			case 'colgroup':
			case 'tbody':
			case 'tfoot':
			case 'thead':
				if ( !$stack->isInTableScope( 'tbody' )
					&& !$stack->isInTableScope( 'thead' )
					&& !$stack->isInTableScope( 'tfoot' )
				) {
					$builder->error( "<$name> encountered in table body mode " .
						"when there is no tbody/thead/tfoot in scope", $sourceStart );
					return;
				}
				$builder->clearStackBack( self::$tableBodyContext, $sourceStart );
				$builder->pop( $sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_TABLE )
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
			case 'tbody':
			case 'tfoot':
			case 'thead':
				if ( !$stack->isInTableScope( $name ) ) {
					$builder->error( "</$name> found but no $name in scope", $sourceStart );
					return;
				}
				$builder->clearStackBack( self::$tableBodyContext, $sourceStart );
				$builder->pop( $sourceStart, $sourceLength );
				$dispatcher->switchMode( Dispatcher::IN_TABLE );
				break;

			case 'body':
			case 'caption':
			case 'col':
			case 'colgroup':
			case 'html':
			case 'td':
			case 'th':
			case 'tr':
				$builder->error( "</$name> found in table body mode, ignoring", $sourceStart );
				return;

			default:
				$dispatcher->inTable->endTag( $name, $sourceStart, $sourceLength );
		}
	}

	public function endDocument( $pos ) {
		$this->dispatcher->inTable->endDocument( $pos );
	}
}
