<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use CoreTagHooks;
use FormatJson;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;

class TextLibrary extends LibraryBase {
	// Matches Lua mw.text constants
	private const JSON_PRESERVE_KEYS = 1;
	private const JSON_TRY_FIXING = 2;
	private const JSON_PRETTY = 4;

	public function register() {
		$lib = [
			'unstrip' => [ $this, 'textUnstrip' ],
			'unstripNoWiki' => [ $this, 'textUnstripNoWiki' ],
			'killMarkers' => [ $this, 'killMarkers' ],
			'getEntityTable' => [ $this, 'getEntityTable' ],
			'jsonEncode' => [ $this, 'jsonEncode' ],
			'jsonDecode' => [ $this, 'jsonDecode' ],
		];
		$opts = [
			'comma' => wfMessage( 'comma-separator' )->inContentLanguage()->text(),
			'and' => wfMessage( 'and' )->inContentLanguage()->text() .
				wfMessage( 'word-separator' )->inContentLanguage()->text(),
			'ellipsis' => wfMessage( 'ellipsis' )->inContentLanguage()->text(),
			'nowiki_protocols' => [],
		];

		$urlProtocols = MediaWikiServices::getInstance()->getMainConfig()->get( MainConfigNames::UrlProtocols );
		foreach ( $urlProtocols as $prot ) {
			if ( substr( $prot, -1 ) === ':' ) {
				// To convert the protocol into a case-insensitive Lua pattern,
				// we need to replace letters with a character class like [Xx]
				// and insert a '%' before various punctuation.
				$prot = preg_replace_callback( '/([a-zA-Z])|([()^$%.\[\]*+?-])/', static function ( $m ) {
					if ( $m[1] ) {
						return '[' . strtoupper( $m[1] ) . strtolower( $m[1] ) . ']';
					} else {
						return '%' . $m[2];
					}
				}, substr( $prot, 0, -1 ) );
				$opts['nowiki_protocols']["($prot):"] = '%1&#58;';
			}
		}

		return $this->getEngine()->registerInterface( 'mw.text.lua', $lib, $opts );
	}

	/**
	 * Handler for textUnstrip
	 * @internal
	 * @param string $s
	 * @return string[]
	 */
	public function textUnstrip( $s ) {
		$this->checkType( 'unstrip', 1, $s, 'string' );
		$stripState = $this->getParser()->getStripState();
		return [ $stripState->killMarkers( $stripState->unstripNoWiki( $s ) ) ];
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function processNoWikis( string $text ): string {
		$content = preg_replace( "#</?nowiki[^>]*>#i", '', $text );
		return $content ? CoreTagHooks::nowiki( $content, [], $this->getParser() )[0] : '';
	}

	/**
	 * Handler for textUnstripNoWiki
	 * @internal
	 * @param string $s
	 * @param bool $getOrigTextWhenPreprocessing
	 * @return string[]
	 */
	public function textUnstripNoWiki( $s, $getOrigTextWhenPreprocessing ) {
		$this->checkType( 'unstripNoWiki', 1, $s, 'string' );
		if ( !$getOrigTextWhenPreprocessing ) {
			return [ $this->getParser()->getStripState()->replaceNoWikis( $s, [ $this, "processNowikis" ] ) ];
		} else {
			return [ $this->getParser()->getStripState()->unstripNoWiki( $s ) ];
		}
	}

	/**
	 * Handler for killMarkers
	 * @internal
	 * @param string $s
	 * @return string[]
	 */
	public function killMarkers( $s ) {
		$this->checkType( 'killMarkers', 1, $s, 'string' );
		return [ $this->getParser()->getStripState()->killMarkers( $s ) ];
	}

	/**
	 * Handler for getEntityTable
	 * @internal
	 * @return array[]
	 */
	public function getEntityTable() {
		$table = array_flip(
			get_html_translation_table( HTML_ENTITIES, ENT_QUOTES | ENT_HTML5, "UTF-8" )
		);
		return [ $table ];
	}

	/**
	 * Handler for jsonEncode
	 * @internal
	 * @param mixed $value
	 * @param string|int $flags
	 * @return string[]
	 */
	public function jsonEncode( $value, $flags ) {
		$this->checkTypeOptional( 'mw.text.jsonEncode', 2, $flags, 'number', 0 );
		$flags = (int)$flags;
		if ( !( $flags & self::JSON_PRESERVE_KEYS ) && is_array( $value ) ) {
			$value = self::reindexArrays( $value, true );
		}
		$ret = FormatJson::encode( $value, (bool)( $flags & self::JSON_PRETTY ), FormatJson::ALL_OK );
		if ( $ret === false ) {
			throw new LuaError( 'mw.text.jsonEncode: Unable to encode value' );
		}
		return [ $ret ];
	}

	/**
	 * Handler for jsonDecode
	 * @internal
	 * @param string $s
	 * @param string|int $flags
	 * @return array
	 */
	public function jsonDecode( $s, $flags ) {
		$this->checkType( 'mw.text.jsonDecode', 1, $s, 'string' );
		$this->checkTypeOptional( 'mw.text.jsonDecode', 2, $flags, 'number', 0 );
		$flags = (int)$flags;
		$opts = FormatJson::FORCE_ASSOC;
		if ( $flags & self::JSON_TRY_FIXING ) {
			$opts |= FormatJson::TRY_FIXING;
		}
		$status = FormatJson::parse( $s, $opts );
		if ( !$status->isOk() ) {
			throw new LuaError( 'mw.text.jsonDecode: ' . $status->getMessage()->text() );
		}
		$val = $status->getValue();
		if ( !( $flags & self::JSON_PRESERVE_KEYS ) && is_array( $val ) ) {
			$val = self::reindexArrays( $val, false );
		}
		return [ $val ];
	}

	/** Recursively reindex array with integer keys to 0-based or 1-based
	 * @param array $arr
	 * @param bool $isEncoding
	 * @return array
	 * @internal
	 */
	public static function reindexArrays( array $arr, $isEncoding ) {
		if ( $isEncoding ) {
			ksort( $arr, SORT_NUMERIC );
			$next = 1;
		} else {
			$next = 0;
		}
		$isSequence = true;
		foreach ( $arr as $k => &$v ) {
			if ( is_array( $v ) ) {
				$v = self::reindexArrays( $v, $isEncoding );
			}

			if ( $isSequence ) {
				if ( is_int( $k ) ) {
					$isSequence = $next++ === $k;
				} elseif ( $isEncoding && ctype_digit( $k ) ) {
					// json_decode currently doesn't return integer keys for {}
					$isSequence = $next++ === (int)$k;
				} else {
					$isSequence = false;
				}
			}
		}
		if ( $isSequence ) {
			if ( $isEncoding ) {
				return array_values( $arr );
			} else {
				return $arr ? array_combine( range( 1, count( $arr ) ), $arr ) : $arr;
			}
		}
		return $arr;
	}

}
