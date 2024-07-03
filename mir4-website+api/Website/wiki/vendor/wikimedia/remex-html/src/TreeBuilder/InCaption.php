<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "in caption" insertion mode
 */
class InCaption extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->dispatcher->inBody->characters( $text, $start, $length,
			$sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
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
				$builder->error( "start tag <$name> not allowed in caption", $sourceStart );
				if ( !$stack->isInTableScope( 'caption' ) ) {
					// Ignore
					return;
				}
				$builder->popAllUpToName( 'caption', $sourceStart, 0 );
				$builder->afe->clearToMarker();
				$this->dispatcher->switchMode( Dispatcher::IN_TABLE )
					->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				break;

			default:
				$this->dispatcher->inBody->startTag( $name, $attrs, $selfClose,
					$sourceStart, $sourceLength );
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$dispatcher = $this->dispatcher;
		$builder = $this->builder;
		$stack = $builder->stack;

		switch ( $name ) {
			case 'caption':
				if ( !$stack->isInTableScope( 'caption' ) ) {
					$builder->error( "</caption> matches a start tag which is not in scope, ignoring",
						$sourceStart );
					return;
				}

				$builder->generateImpliedEndTags( false, $sourceStart );
				if ( $stack->current->htmlName !== 'caption' ) {
					$builder->error( "</caption> found but another element is open", $sourceStart );
				}
				$builder->popAllUpToName( 'caption', $sourceStart, $sourceLength );
				$builder->afe->clearToMarker();
				$dispatcher->switchMode( Dispatcher::IN_TABLE );
				break;

			case 'table':
				if ( !$stack->isInTableScope( 'caption' ) ) {
					$builder->error( '</table> found in caption, but there is no ' .
						'caption in scope, ignoring', $sourceStart );
					return;
				}
				$builder->generateImpliedEndTags( false, $sourceStart );
				if ( $stack->current->htmlName !== 'caption' ) {
					$builder->error( '</table> found in caption, closing caption', $sourceStart );
				}
				$builder->popAllUpToName( 'caption', $sourceStart, 0 );
				$builder->afe->clearToMarker();
				$dispatcher->switchMode( Dispatcher::IN_TABLE )
					->endTag( $name, $sourceStart, $sourceLength );
				break;

			case 'body':
			case 'col':
			case 'colgroup':
			case 'html':
			case 'tbody':
			case 'td':
			case 'tfoot':
			case 'th':
			case 'thead':
			case 'tr':
				$this->builder->error( "end tag </$name> ignored in caption mode", $sourceStart );
				break;

			default:
				$this->dispatcher->inBody->endTag( $name, $sourceStart, $sourceLength );
		}
	}

	public function endDocument( $pos ) {
		$this->dispatcher->inBody->endDocument( $pos );
	}
}
