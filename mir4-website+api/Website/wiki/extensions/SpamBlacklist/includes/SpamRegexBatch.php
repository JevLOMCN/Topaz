<?php

namespace MediaWiki\Extension\SpamBlacklist;

use Wikimedia\AtEase\AtEase;

/**
 * Utility class for working with blacklists
 */
class SpamRegexBatch {
	/**
	 * Build a set of regular expressions matching URLs with the list of regex fragments.
	 * Returns an empty list if the input list is empty.
	 *
	 * @param string[] $lines list of fragments which will match in URLs
	 * @param BaseBlacklist $blacklist
	 * @param int $batchSize largest allowed batch regex;
	 *                       if 0, will produce one regex per line
	 * @return string[]
	 */
	private static function buildRegexes( array $lines, BaseBlacklist $blacklist, $batchSize = 4096 ) {
		# Make regex
		# It's faster using the S modifier even though it will usually only be run once
		// $regex = 'https?://+[a-z0-9_\-.]*(' . implode( '|', $lines ) . ')';
		// return '/' . str_replace( '/', '\/', preg_replace('|\\\*/|', '/', $regex) ) . '/Sim';
		$regexes = [];
		$regexStart = $blacklist->getRegexStart();
		$regexEnd = $blacklist->getRegexEnd( $batchSize );
		$build = false;
		foreach ( $lines as $line ) {
			if ( substr( $line, -1, 1 ) == "\\" ) {
				// Final \ will break silently on the batched regexes.
				// Skip it here to avoid breaking the next line;
				// warnings from getBadLines() will still trigger on
				// edit to keep new ones from floating in.
				continue;
			}
			// FIXME: not very robust size check, but should work. :)
			if ( $build === false ) {
				$build = $line;
			} elseif ( strlen( $build ) + strlen( $line ) > $batchSize ) {
				$regexes[] = $regexStart .
					str_replace( '/', '\/', preg_replace( '|\\\*/|u', '/', $build ) ) .
					$regexEnd;
				$build = $line;
			} else {
				$build .= '|';
				$build .= $line;
			}
		}
		if ( $build !== false ) {
			$regexes[] = $regexStart .
				str_replace( '/', '\/', preg_replace( '|\\\*/|u', '/', $build ) ) .
				$regexEnd;
		}
		return $regexes;
	}

	/**
	 * Confirm that a set of regexes is either empty or valid.
	 *
	 * @param string[] $regexes set of regexes
	 * @return bool true if ok, false if contains invalid lines
	 */
	private static function validateRegexes( $regexes ) {
		foreach ( $regexes as $regex ) {
			AtEase::suppressWarnings();
			// @phan-suppress-next-line PhanParamSuspiciousOrder False positive
			$ok = preg_match( $regex, '' );
			AtEase::restoreWarnings();

			if ( $ok === false ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Strip comments and whitespace, then remove blanks
	 *
	 * @param string[] $lines
	 * @return string[]
	 */
	private static function stripLines( array $lines ) {
		return array_filter(
			array_map( 'trim',
				preg_replace( '/#.*$/', '',
					$lines )
			)
		);
	}

	/**
	 * Do a sanity check on the batch regex.
	 *
	 * @param string[] $lines unsanitized input lines
	 * @param BaseBlacklist $blacklist
	 * @param bool|string $fileName optional for debug reporting
	 * @return string[] of regexes
	 */
	private static function buildSafeRegexes( array $lines, BaseBlacklist $blacklist, $fileName = false ) {
		$lines = self::stripLines( $lines );
		$regexes = self::buildRegexes( $lines, $blacklist );
		if ( self::validateRegexes( $regexes ) ) {
			return $regexes;
		} else {
			// _Something_ broke... rebuild line-by-line; it'll be
			// slower if there's a lot of blacklist lines, but one
			// broken line won't take out hundreds of its brothers.
			if ( $fileName ) {
				wfDebugLog( 'SpamBlacklist', "Spam blacklist warning: bogus line in $fileName\n" );
			}
			return self::buildRegexes( $lines, $blacklist, 0 );
		}
	}

	/**
	 * Returns an array of invalid lines
	 *
	 * @param string[] $lines
	 * @param BaseBlacklist $blacklist
	 * @return string[] of input lines which produce invalid input, or empty array if no problems
	 */
	public static function getBadLines( $lines, BaseBlacklist $blacklist ) {
		$lines = self::stripLines( $lines );

		$badLines = [];
		foreach ( $lines as $line ) {
			if ( substr( $line, -1, 1 ) == "\\" ) {
				// Final \ will break silently on the batched regexes.
				$badLines[] = $line;
			}
		}

		$regexes = self::buildRegexes( $lines, $blacklist );
		if ( self::validateRegexes( $regexes ) ) {
			// No other problems!
			return $badLines;
		}

		// Something failed in the batch, so check them one by one.
		foreach ( $lines as $line ) {
			$regexes = self::buildRegexes( [ $line ], $blacklist );
			if ( !self::validateRegexes( $regexes ) ) {
				$badLines[] = $line;
			}
		}
		return $badLines;
	}

	/**
	 * Build a set of regular expressions from the given multiline input text,
	 * with empty lines and comments stripped.
	 *
	 * @param string $source
	 * @param BaseBlacklist $blacklist
	 * @param bool|string $fileName optional, for reporting of bad files
	 * @return string[] of regular expressions, potentially empty
	 */
	public static function regexesFromText( $source, BaseBlacklist $blacklist, $fileName = false ) {
		$lines = explode( "\n", $source );
		return self::buildSafeRegexes( $lines, $blacklist, $fileName );
	}

	/**
	 * Build a set of regular expressions from a MediaWiki message.
	 * Will be correctly empty if the message isn't present.
	 *
	 * @param string $message
	 * @param BaseBlacklist $blacklist
	 * @return string[] of regular expressions, potentially empty
	 */
	public static function regexesFromMessage( $message, BaseBlacklist $blacklist ) {
		$source = wfMessage( $message )->inContentLanguage();
		if ( !$source->isDisabled() ) {
			return self::regexesFromText( $source->plain(), $blacklist );
		} else {
			return [];
		}
	}
}
