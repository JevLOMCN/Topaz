<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "after frameset" insertion mode
 */
class AfterFrameset extends InsertionMode {
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

			case 'noframes':
				$dispatcher->inHead->startTag(
					$name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			default:
				$builder->error( "unexpected start tag after frameset, ignoring", $sourceStart );
				return;
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$dispatcher = $this->dispatcher;

		switch ( $name ) {
			case 'html':
				$dispatcher->switchMode( Dispatcher::AFTER_AFTER_FRAMESET );
				break;

			default:
				$builder->error( "unexpected end tag after frameset, ignoring", $sourceStart );
		}
	}

	public function endDocument( $pos ) {
		$this->builder->stopParsing( $pos );
	}
}
