<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\Tokenizer\PlainAttributes;

/**
 * The "in table" insertion mode
 */
class InTable extends InsertionMode {
	/**
	 * The tag names that are cleared when we "clear the stack back to a table context"
	 * @var array<string,bool>
	 */
	private static $tableContext = [
		'table' => true,
		'template' => true,
		'html' => true
	];

	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$allowed = [
			'table' => true,
			'tbody' => true,
			'tfoot' => true,
			'thead' => true,
			'tr' => true
		];
		if ( isset( $allowed[$this->builder->stack->current->htmlName] ) ) {
			$this->builder->pendingTableCharacters = [];
			$this->dispatcher->switchAndSave( Dispatcher::IN_TABLE_TEXT )
				->characters( $text, $start, $length, $sourceStart, $sourceLength );
		} else {
			$this->builder->error( 'unexpected text in table, fostering', $sourceStart );
			$this->builder->fosterParenting = true;
			$this->dispatcher->inBody->characters(
				$text, $start, $length, $sourceStart, $sourceLength );
			$this->builder->fosterParenting = false;
		}
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;
		$stack = $builder->stack;

		switch ( $name ) {
			case 'caption':
				$builder->clearStackBack( self::$tableContext, $sourceStart );
				$builder->afe->insertMarker();
				$dispatcher->switchMode( Dispatcher::IN_CAPTION );
				$builder->insertElement( $name, $attrs, false,
					$sourceStart, $sourceLength );
				break;

			case 'colgroup':
				$builder->clearStackBack( self::$tableContext, $sourceStart );
				$dispatcher->switchMode( Dispatcher::IN_COLUMN_GROUP );
				$builder->insertElement( $name, $attrs, false,
					$sourceStart, $sourceLength );
				break;

			case 'col':
				$builder->clearStackBack( self::$tableContext, $sourceStart );
				$builder->insertElement( 'colgroup', new PlainAttributes, false,
					$sourceStart, 0 );
				$dispatcher->switchMode( Dispatcher::IN_COLUMN_GROUP )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'tbody':
			case 'tfoot':
			case 'thead':
				$builder->clearStackBack( self::$tableContext, $sourceStart );
				$builder->insertElement( $name, $attrs, false,
					$sourceStart, $sourceLength );
				$dispatcher->switchMode( Dispatcher::IN_TABLE_BODY );
				break;

			case 'td':
			case 'th':
			case 'tr':
				$builder->clearStackBack( self::$tableContext, $sourceStart );
				$builder->insertElement( 'tbody', new PlainAttributes, false,
					$sourceStart, $sourceLength );
				$dispatcher->switchMode( Dispatcher::IN_TABLE_BODY )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'table':
				$builder->error( 'unexpected <table> in table', $sourceStart );
				if ( !$stack->isInTableScope( 'table' ) ) {
					// Ignore
					break;
				}
				$builder->popAllUpToName( 'table', $sourceStart, 0 );
				$dispatcher->reset()
				->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'style':
			case 'script':
			case 'template':
				$dispatcher->inHead->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			case 'form':
				if ( $stack->hasTemplate() || $builder->formElement !== null ) {
					$builder->error( 'invalid form in table, ignoring', $sourceStart );
					// Ignore
					break;
				}
				$builder->error( 'invalid form in table, inserting void element', $sourceStart );
				$elt = $builder->insertElement( 'form', $attrs, true,
					$sourceStart, $sourceLength );
				$builder->formElement = $elt;
				break;

			case 'input':
				if ( isset( $attrs['type'] ) && strcasecmp( $attrs['type'], 'hidden' ) === 0 ) {
					$builder->error( 'begrudgingly accepting a hidden input in table mode',
						$sourceStart );
					$dispatcher->ack = true;
					$builder->insertElement( $name, $attrs, true, $sourceStart, $sourceLength );
					break;
				}
				// Fall through

			default:
				$builder->error( 'invalid start tag in table, fostering', $sourceStart );
				$builder->fosterParenting = true;
				$dispatcher->inBody->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				$builder->fosterParenting = false;
				break;
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'table':
				if ( !$stack->isInTableScope( 'table' ) ) {
					$builder->error( '</table> found but no table element in scope, ignoring', $sourceStart );
					// Ignore
					break;
				}
				$builder->popAllUpToName( 'table', $sourceStart, $sourceLength );
				$dispatcher->reset();
				break;

			case 'body':
			case 'caption':
			case 'col':
			case 'colgroup':
			case 'html':
			case 'tbody':
			case 'td':
			case 'tfoot':
			case 'th':
			case 'thead':
			case 'tr':
				$builder->error( 'ignoring invalid end tag inside table', $sourceStart );
				break;

			case 'template':
				$dispatcher->inHead->endTag( $name, $sourceStart, $sourceLength );
				break;

			default:
				$builder->error( 'unexpected end tag in table, fostering', $sourceStart );
				$builder->fosterParenting = true;
				$dispatcher->inBody->endTag( $name, $sourceStart, $sourceLength );
				$builder->fosterParenting = false;
		}
	}

	public function endDocument( $pos ) {
		$this->dispatcher->inBody->endDocument( $pos );
	}
}
