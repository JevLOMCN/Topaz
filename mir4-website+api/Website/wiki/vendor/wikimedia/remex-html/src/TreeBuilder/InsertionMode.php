<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\PropGuard;
use Wikimedia\RemexHtml\Tokenizer\Attributes;

abstract class InsertionMode {
	use PropGuard;

	/** @var TreeBuilder */
	protected $builder;

	/** @var Dispatcher */
	protected $dispatcher;

	public function __construct( TreeBuilder $builder, Dispatcher $dispatcher ) {
		$this->builder = $builder;
		$this->dispatcher = $dispatcher;
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->builder->error( "unexpected doctype", $sourceStart );
	}

	public function comment( $text, $sourceStart, $sourceLength ) {
		$this->builder->comment( null, $text, $sourceStart, $sourceLength );
	}

	public function error( $text, $pos ) {
		$this->builder->error( $text, $pos );
	}

	protected function splitInitialMatch( $isStartOfToken, $mask, $text, $start, $length,
		$sourceStart, $sourceLength
	) {
		$matchLength = strspn( $text, $mask, $start, $length );
		if ( $isStartOfToken && $matchLength ) {
			// Do some extra work to figure out a plausible start position if
			// the text node started with <![CDATA[
			// FIXME: make this optional?
			$sourceText = $this->builder->tokenizer->getPreprocessedText();
			$isCdata = substr_compare( $sourceText, '<![CDATA[', $sourceStart, $sourceLength ) === 0;
			$cdataLength = $isCdata ? strlen( '<![CDATA[' ) : 0;
		} else {
			$cdataLength = 0;
		}

		return [
			[
				$start,
				$matchLength,
				$sourceStart,
				$matchLength + $cdataLength,
			], [
				$start + $matchLength,
				$length - $matchLength,
				$sourceStart + $matchLength + $cdataLength,
				$sourceLength - $matchLength - $cdataLength
			]
		];
	}

	protected function handleFramesetWhitespace( $inBody, $text, $start, $length,
		$sourceStart, $sourceLength
	) {
		$isStartOfToken = true;
		$builder = $this->builder;

		do {
			[ $part1, $part2 ] = $this->splitInitialMatch(
				$isStartOfToken, "\t\n\f\r ", $text, $start, $length, $sourceStart, $sourceLength );
			$isStartOfToken = false;

			[ $start, $length, $sourceStart, $sourceLength ] = $part1;
			if ( $length ) {
				if ( $inBody ) {
					$this->dispatcher->inBody->characters( $text, $start, $length,
						$sourceStart, $sourceLength );
				} else {
					$builder->insertCharacters( $text, $start, $length, $sourceStart, $sourceLength );
				}
			}

			[ $start, $length, $sourceStart, $sourceLength ] = $part2;
			if ( $length ) {
				$builder->error( "unexpected non-whitespace character", $sourceStart );
				$start++;
				$length--;
				$sourceStart++;
				$sourceLength--;
			}
		} while ( $length > 0 );
	}

	protected function stripNulls( $callback, $text, $start, $length, $sourceStart, $sourceLength ) {
		$errorOffset = $sourceStart - $start;
		while ( $length > 0 ) {
			$validLength = strcspn( $text, "\0", $start, $length );
			if ( $validLength ) {
				$callback( $text, $start, $validLength, $sourceStart, $sourceLength );
				$start += $validLength;
				$length -= $validLength;
			}
			if ( $length <= 0 ) {
				break;
			}
			$this->error( 'unexpected null character', $start + $errorOffset );
			$start++;
			$length--;
		}
	}

	abstract public function characters( $text, $start, $length, $sourceStart, $sourceLength );

	abstract public function startTag( $name, Attributes $attrs, $selfClose,
		$sourceStart, $sourceLength );

	abstract public function endTag( $name, $sourceStart, $sourceLength );

	abstract public function endDocument( $pos );
}
