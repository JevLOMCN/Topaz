<?php

namespace TextExtracts;

use MediaWiki\MediaWikiServices;

/**
 * This class needs to understand HTML as well as plain text. It tries to not break HTML tags, but
 * might break pairs of tags, leaving unclosed tags behind. We can tidy the output to fix
 * this.
 *
 * @license GPL-2.0-or-later
 */
class TextTruncator {
	/**
	 * @var bool Whether to tidy the output
	 */
	private $useTidy;

	/**
	 * @param bool $useTidy
	 */
	public function __construct( bool $useTidy ) {
		$this->useTidy = $useTidy;
	}

	/**
	 * Returns no more than the given number of sentences
	 *
	 * @param string $text Source text to extract from
	 * @param int $requestedSentenceCount Maximum number of sentences to extract
	 * @return string
	 */
	public function getFirstSentences( $text, $requestedSentenceCount ) {
		if ( $requestedSentenceCount <= 0 ) {
			return '';
		}

		// Based on code from OpenSearchXml by Brion Vibber
		$endchars = [
			// regular ASCII
			'\P{Lu}\.(?=[ \n]|$)',
			'[!?](?=[ \n]|$)',
			// full-width ideographic full-stop
			'。',
			// double-width roman forms
			'．',
			'！',
			'？',
			// half-width ideographic full stop
			'｡',
		];

		$regexp = '/(?:' . implode( '|', $endchars ) . ')+/u';
		$res = preg_match_all( $regexp, $text, $matches, PREG_OFFSET_CAPTURE );

		if ( !$res ) {
			// Just return the first line
			$lines = explode( "\n", $text, 2 );
			return trim( $lines[0] );
		}

		$index = min( $requestedSentenceCount, $res ) - 1;
		[ $tail, $length ] = $matches[0][$index];
		// PCRE returns raw offsets, so using substr() instead of mb_substr()
		$text = substr( $text, 0, $length ) . $tail;

		return $this->tidy( $text );
	}

	/**
	 * Returns no more than a requested number of characters, preserving words
	 *
	 * @param string $text Source text to extract from
	 * @param int $requestedLength Maximum number of characters to return
	 * @return string
	 */
	public function getFirstChars( $text, $requestedLength ) {
		if ( $requestedLength <= 0 ) {
			return '';
		}

		$length = mb_strlen( $text );
		if ( $length <= $requestedLength ) {
			return $text;
		}

		// This ungreedy pattern always matches, just might return an empty string
		$pattern = '/^[\w\/]*>?/su';
		preg_match( $pattern, mb_substr( $text, $requestedLength ), $m );
		$truncatedText = mb_substr( $text, 0, $requestedLength ) . $m[0];
		if ( $truncatedText === $text ) {
			return $text;
		}

		return $this->tidy( $truncatedText );
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function tidy( $text ) {
		if ( $this->useTidy ) {
			$text = MediaWikiServices::getInstance()->getTidy()->tidy( $text );
		}

		return trim( $text );
	}

}
