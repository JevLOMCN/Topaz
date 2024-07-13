<?php
/**
 * Copyright (C) 2021 Kunal Mehta <legoktm@debian.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\SyntaxHighlight;

use MediaWiki\MediaWikiServices;
use Shellbox\Command\BoxedCommand;
use Shellbox\ShellboxError;

/**
 * Wrapper around the `pygmentize` command
 */
class Pygmentize {

	/**
	 * If no pygmentize is configured, use bundled
	 */
	public static function useBundled(): bool {
		global $wgPygmentizePath;
		return $wgPygmentizePath === false;
	}

	/**
	 * Get a real path to pygmentize
	 */
	private static function getPath(): string {
		global $wgPygmentizePath;

		// If $wgPygmentizePath is unset, use the bundled copy.
		return $wgPygmentizePath ?: __DIR__ . '/../pygments/pygmentize';
	}

	/**
	 * Get the version of pygments (cached)
	 */
	public static function getVersion(): string {
		static $version;
		if ( $version !== null ) {
			return $version;
		}
		if ( self::useBundled() ) {
			$version = self::getBundledVersion();
			return $version;
		}

		// This is called a lot, during both page views, edits, and load.php startup request.
		// It also gets called multiple times during the same request. As such, prefer
		// low latency via php-apcu.
		//
		// This value also controls cache invalidation and propagation through embedding
		// in other keys from this class, and thus has a low expiry. Avoid latency from
		// frequent cache misses by by sharing the values with other servers via Memcached
		// as well.

		$srvCache = MediaWikiServices::getInstance()->getLocalServerObjectCache();
		return $srvCache->getWithSetCallback(
			$srvCache->makeGlobalKey( 'pygmentize-version' ),
			// Spread between 55 min and 1 hour
			mt_rand( 55 * $srvCache::TTL_MINUTE, 60 * $srvCache::TTL_MINUTE ),
			static function () {
				$wanCache = MediaWikiServices::getInstance()->getMainWANObjectCache();
				return $wanCache->getWithSetCallback(
					$wanCache->makeGlobalKey( 'pygmentize-version' ),
					// Must be under 55 min to avoid renewing stale data in upper layer
					30 * $wanCache::TTL_MINUTE,
					[ __CLASS__, 'fetchVersion' ]
				);
			}
		);
	}

	/**
	 * Get the version of bundled pygments
	 */
	private static function getBundledVersion(): string {
		return trim( file_get_contents( __DIR__ . '/../pygments/VERSION' ) );
	}

	/**
	 * Shell out to get installed pygments version
	 *
	 * @internal For use by WANObjectCache/BagOStuff only
	 */
	public static function fetchVersion(): string {
		$result = self::boxedCommand()
			->params( self::getPath(), '-V' )
			->includeStderr()
			->execute();
		self::recordShellout( 'version' );

		$output = $result->getStdout();
		if ( $result->getExitCode() != 0 ||
			!preg_match( '/^Pygments version (\S+),/', $output, $matches )
		) {
			throw new PygmentsException( $output );
		}

		return $matches[1];
	}

	/**
	 * Get the pygments generated CSS (cached)
	 *
	 * Note: if using bundled, the CSS is already available
	 * in modules/pygments.generated.css.
	 */
	public static function getGeneratedCSS(): string {
		// This is rarely called as the result gets HTTP-cached via long-expiry load.php.
		// When it gets called once, after a deployment, during that brief spike of
		// dedicated requests from each wiki. Leverage Memcached to share this.
		// Its likely not needed again on the same server for a while after that.
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		return $cache->getWithSetCallback(
			$cache->makeGlobalKey( 'pygmentize-css', self::getVersion() ),
			$cache::TTL_WEEK,
			[ __CLASS__, 'fetchGeneratedCSS' ]
		);
	}

	/**
	 * Shell out to get generated CSS from pygments
	 *
	 * @internal Only public for updateCSS.php
	 */
	public static function fetchGeneratedCSS(): string {
		$result = self::boxedCommand()
			->params(
				self::getPath(), '-f', 'html',
				'-S', 'default', '-a', '.mw-highlight' )
			->includeStderr()
			->execute();
		self::recordShellout( 'generated_css' );
		$output = $result->getStdout();
		if ( $result->getExitCode() != 0 ) {
			throw new PygmentsException( $output );
		}
		return $output;
	}

	/**
	 * Get the list of supported lexers by pygments (cached)
	 *
	 * @return array<string,true>
	 */
	public static function getLexers(): array {
		if ( self::useBundled() ) {
			return require __DIR__ . '/../SyntaxHighlight.lexers.php';
		}

		// This is called during page views and edits, and may be called
		// repeatedly. Trade low latency for higher shell rate by caching
		// on each server separately. This is made up for with a high TTL,
		// which is fine because we vary by version, thus ensuring quick
		// propagation separate from the TTL.
		$cache = MediaWikiServices::getInstance()->getLocalServerObjectCache();
		return $cache->getWithSetCallback(
			$cache->makeGlobalKey( 'pygmentize-lexers', self::getVersion() ),
			$cache::TTL_WEEK,
			[ __CLASS__, 'fetchLexers' ]
		);
	}

	/**
	 * Determine if the pygments command line supports the --json option
	 *
	 * @return bool
	 */
	private static function pygmentsSupportsJsonOutput(): bool {
		$version = self::getVersion();
		return ( version_compare( $version, '2.11.0' ) !== -1 );
	}

	/**
	 * Shell out to get supported lexers by pygments
	 *
	 * @internal Only public for updateLexerList.php
	 * @return array<string,true>
	 */
	public static function fetchLexers(): array {
		$cliParams = [ self::getPath(), '-L', 'lexer' ];
		if ( self::pygmentsSupportsJsonOutput() ) {
			$cliParams[] = '--json';
		}

		$result = self::boxedCommand()
			->params( $cliParams )
			->includeStderr()
			->execute();
		self::recordShellout( 'fetch_lexers' );
		$output = $result->getStdout();
		if ( $result->getExitCode() != 0 ) {
			throw new PygmentsException( $output );
		}

		if ( self::pygmentsSupportsJsonOutput() ) {
			$lexers = self::parseLexersFromJson( $output );
		} else {
			$lexers = self::parseLexersFromText( $output );
		}

		sort( $lexers );
		return array_fill_keys( $lexers, true );
	}

	/**
	 * Parse json output of the pygments lexers list and return as php array
	 *
	 * @param string $output JSON formatted output of pygments lexers list
	 * @return array
	 */
	private static function parseLexersFromJson( $output ): array {
		$data = json_decode( $output, true );
		if ( $data === null ) {
			throw new PygmentsException(
				'Got invalid JSON from Pygments: ' . $output );
		}
		$lexers = [];
		foreach ( array_values( $data['lexers'] ) as $lexer ) {
			$lexers = array_merge( $lexers, $lexer['aliases'] );
		}
		return $lexers;
	}

	/**
	 * Parse original stdout of the pygments lexers list
	 * This was the only format available before pygments 2.11.0
	 * NOTE: Should be removed when pygments 2.11 is the minimum version expected to be installed
	 *
	 * @param string $output Textual list of pygments lexers
	 * @return array
	 */
	private static function parseLexersFromText( $output ): array {
		$lexers = [];
		foreach ( explode( "\n", $output ) as $line ) {
			if ( str_starts_with( $line, '*' ) ) {
				$newLexers = explode( ', ', trim( $line, "* :\r\n" ) );

				// Skip internal, unnamed lexers
				if ( $newLexers[0] !== '' ) {
					$lexers = array_merge( $lexers, $newLexers );
				}
			}
		}
		return $lexers;
	}

	/**
	 * Actually highlight some text
	 *
	 * @param string $lexer Lexer name
	 * @param string $code Code to highlight
	 * @param array $options Options to pass to pygments
	 * @return string
	 */
	public static function highlight( $lexer, $code, array $options ): string {
		$optionPairs = [];
		foreach ( $options as $k => $v ) {
			$optionPairs[] = "{$k}={$v}";
		}
		self::recordShellout( 'highlight' );

		try {
			$result = self::boxedCommand()
				->params(
					self::getPath(),
					'-l', $lexer,
					'-f', 'html',
					'-O', implode( ',', $optionPairs ),
					'file'
				)
				->inputFileFromString( 'file', $code )
				->execute();
		} catch ( ShellboxError $exception ) {
			// If we have trouble sending or receiving over the network to
			// Shellbox, we technically don't know if the command succeed or failed,
			// but, treat the highlight() command as recoverable by wrapping this in
			// PygmentsException. This permits the Parser tag to fallback to
			// plainCodeWrap(), thus avoiding a fatal on pageviews (T292663).
			throw new PygmentsException( 'ShellboxError', 0, $exception );
		}

		$output = $result->getStdout();
		if ( $result->getExitCode() != 0 ) {
			throw new PygmentsException( $output );
		}

		return $output;
	}

	private static function boxedCommand(): BoxedCommand {
		$command = MediaWikiServices::getInstance()->getShellCommandFactory()
			->createBoxed( 'syntaxhighlight' )
			->disableNetwork()
			->firejailDefaultSeccomp()
			->routeName( 'syntaxhighlight-pygments' );

		if ( wfIsWindows() ) {
			// Python requires the SystemRoot environment variable to initialize (T300223)
			$command->environment( [
				'SystemRoot' => getenv( 'SystemRoot' ),
			] );
		}

		return $command;
	}

	/**
	 * Track how often we do each type of shellout in statsd
	 *
	 * @param string $type Type of shellout
	 */
	private static function recordShellout( $type ) {
		$statsd = MediaWikiServices::getInstance()->getStatsdDataFactory();
		$statsd->increment( "syntaxhighlight_shell.$type" );
	}
}
