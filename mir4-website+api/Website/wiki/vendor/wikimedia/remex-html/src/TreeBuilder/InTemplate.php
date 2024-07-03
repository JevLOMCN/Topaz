<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "in template" insertion mode
 */
class InTemplate extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->dispatcher->inBody->characters(
			$text, $start, $length, $sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
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
				$dispatcher->inHead->startTag(
					$name, $attrs, $selfClose, $sourceStart, $sourceLength );
				return;

			case 'caption':
			case 'colgroup':
			case 'tbody':
			case 'tfoot':
			case 'thead':
				$mode = Dispatcher::IN_TABLE;
				break;

			case 'col':
				$mode = Dispatcher::IN_COLUMN_GROUP;
				break;

			case 'tr':
				$mode = Dispatcher::IN_TABLE_BODY;
				break;

			case 'td':
			case 'th':
				$mode = Dispatcher::IN_ROW;
				break;

			default:
				$mode = Dispatcher::IN_BODY;
				break;
		}

		$dispatcher->templateModeStack->pop();
		$dispatcher->templateModeStack->push( $mode );
		$dispatcher->switchMode( $mode )
			->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'template':
				$dispatcher->inHead->endTag( $name, $sourceStart, $sourceLength );
				break;

			default:
				$this->builder->error( "unexpected </$name> in template, ignoring", $sourceStart );
				return;
		}
	}

	public function endDocument( $pos ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		if ( !$stack->hasTemplate() ) {
			$builder->stopParsing( $pos );
			return;
		}

		$builder->error( "unexpected end of file in template", $pos );
		$builder->popAllUpToName( 'template', $pos, 0 );
		$builder->afe->clearToMarker();
		$dispatcher->templateModeStack->pop();
		$dispatcher->reset()
			->endDocument( $pos );
	}
}
