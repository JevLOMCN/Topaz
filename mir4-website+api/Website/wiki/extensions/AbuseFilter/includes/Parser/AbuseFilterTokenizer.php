<?php

namespace MediaWiki\Extension\AbuseFilter\Parser;

use BagOStuff;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleException;

/**
 * Tokenizer for AbuseFilter rules.
 */
class AbuseFilterTokenizer {
	/** @var int Tokenizer cache version. Increment this when changing the syntax. */
	public const CACHE_VERSION = 4;
	private const COMMENT_START_RE = '/\s*\/\*/A';
	private const ID_SYMBOL_RE = '/[0-9A-Za-z_]+/A';
	public const OPERATOR_RE =
		'/(\!\=\=|\!\=|\!|\*\*|\*|\/|\+|\-|%|&|\||\^|\:\=|\?|\:|\<\=|\<|\>\=|\>|\=\=\=|\=\=|\=)/A';
	private const BASE = '0(?<base>[xbo])';
	private const DIGIT = '[0-9A-Fa-f]';
	private const DIGITS = self::DIGIT . '+' . '(?:\.\d*)?|\.\d+';
	private const RADIX_RE = '/(?:' . self::BASE . ')?(?<input>' . self::DIGITS . ')(?!\w)/Au';
	private const WHITESPACE = "\011\012\013\014\015\040";

	// Order is important. The punctuation-matching regex requires that
	// ** comes before *, etc. They are sorted to make it easy to spot
	// such errors.
	public const OPERATORS = [
		// Inequality
		'!==', '!=', '!',
		// Multiplication/exponentiation
		'**', '*',
		// Other arithmetic
		'/', '+', '-', '%',
		// Logic
		'&', '|', '^',
		// Setting
		':=',
		// Ternary
		'?', ':',
		// Less than
		'<=', '<',
		// Greater than
		'>=', '>',
		// Equality
		'===', '==', '=',
	];

	public const PUNCTUATION = [
		',' => AFPToken::TCOMMA,
		'(' => AFPToken::TBRACE,
		')' => AFPToken::TBRACE,
		'[' => AFPToken::TSQUAREBRACKET,
		']' => AFPToken::TSQUAREBRACKET,
		';' => AFPToken::TSTATEMENTSEPARATOR,
	];

	public const BASES = [
		'b' => 2,
		'x' => 16,
		'o' => 8
	];

	public const BASE_CHARS_RES = [
		2  => '/^[01]+$/',
		8  => '/^[0-7]+$/',
		16 => '/^[0-9A-Fa-f]+$/',
		10 => '/^[0-9.]+$/',
	];

	public const KEYWORDS = [
		'in', 'like', 'true', 'false', 'null', 'contains', 'matches',
		'rlike', 'irlike', 'regex', 'if', 'then', 'else', 'end',
	];

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @param BagOStuff $cache
	 */
	public function __construct( BagOStuff $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Get a cache key used to store the tokenized code
	 *
	 * @param string $code Not yet tokenized
	 * @return string
	 * @internal
	 */
	public function getCacheKey( $code ) {
		return $this->cache->makeGlobalKey( __CLASS__, self::CACHE_VERSION, crc32( $code ) );
	}

	/**
	 * Get the tokens for the given code.
	 *
	 * @param string $code
	 * @return array[]
	 * @phan-return array<int,array{0:AFPToken,1:int}>
	 */
	public function getTokens( string $code ): array {
		return $this->cache->getWithSetCallback(
			$this->getCacheKey( $code ),
			BagOStuff::TTL_DAY,
			function () use ( $code ) {
				return $this->tokenize( $code );
			}
		);
	}

	/**
	 * @param string $code
	 * @return array[]
	 * @phan-return array<int,array{0:AFPToken,1:int}>
	 */
	private function tokenize( string $code ): array {
		$tokens = [];
		$curPos = 0;

		do {
			$prevPos = $curPos;
			$token = $this->nextToken( $code, $curPos );
			$tokens[ $token->pos ] = [ $token, $curPos ];
		} while ( $curPos !== $prevPos );

		return $tokens;
	}

	/**
	 * @param string $code
	 * @param int &$offset
	 * @return AFPToken
	 * @throws UserVisibleException
	 */
	private function nextToken( $code, &$offset ) {
		$matches = [];
		$start = $offset;

		// Read past comments
		while ( preg_match( self::COMMENT_START_RE, $code, $matches, 0, $offset ) ) {
			if ( strpos( $code, '*/', $offset ) === false ) {
				throw new UserVisibleException(
					'unclosedcomment', $offset, [] );
			}
			$offset = strpos( $code, '*/', $offset ) + 2;
		}

		// Spaces
		$offset += strspn( $code, self::WHITESPACE, $offset );
		if ( $offset >= strlen( $code ) ) {
			return new AFPToken( AFPToken::TNONE, '', $start );
		}

		$chr = $code[$offset];

		// Punctuation
		if ( isset( self::PUNCTUATION[$chr] ) ) {
			$offset++;
			return new AFPToken( self::PUNCTUATION[$chr], $chr, $start );
		}

		// String literal
		if ( $chr === '"' || $chr === "'" ) {
			return self::readStringLiteral( $code, $offset, $start );
		}

		$matches = [];

		// Operators
		if ( preg_match( self::OPERATOR_RE, $code, $matches, 0, $offset ) ) {
			$token = $matches[0];
			$offset += strlen( $token );
			return new AFPToken( AFPToken::TOP, $token, $start );
		}

		// Numbers
		$matchesv2 = [];
		if ( preg_match( self::RADIX_RE, $code, $matchesv2, 0, $offset ) ) {
			$token = $matchesv2[0];
			$baseChar = $matchesv2['base'];
			$input = $matchesv2['input'];
			$base = $baseChar ? self::BASES[$baseChar] : 10;
			if ( preg_match( self::BASE_CHARS_RES[$base], $input ) ) {
				$num = $base !== 10 ? base_convert( $input, $base, 10 ) : $input;
				$offset += strlen( $token );
				return ( strpos( $input, '.' ) !== false )
					? new AFPToken( AFPToken::TFLOAT, floatval( $num ), $start )
					: new AFPToken( AFPToken::TINT, intval( $num ), $start );
			}
		}

		// IDs / Keywords

		if ( preg_match( self::ID_SYMBOL_RE, $code, $matches, 0, $offset ) ) {
			$token = $matches[0];
			$offset += strlen( $token );
			$type = in_array( $token, self::KEYWORDS )
				? AFPToken::TKEYWORD
				: AFPToken::TID;
			return new AFPToken( $type, $token, $start );
		}

		throw new UserVisibleException(
			'unrecognisedtoken', $start, [ substr( $code, $start ) ] );
	}

	/**
	 * @param string $code
	 * @param int &$offset
	 * @param int $start
	 * @return AFPToken
	 * @throws UserVisibleException
	 */
	private static function readStringLiteral( $code, &$offset, $start ) {
		$type = $code[$offset];
		$offset++;
		$length = strlen( $code );
		$token = '';
		while ( $offset < $length ) {
			if ( $code[$offset] === $type ) {
				$offset++;
				return new AFPToken( AFPToken::TSTRING, $token, $start );
			}

			// Performance: Use a PHP function (implemented in C)
			// to scan ahead.
			$addLength = strcspn( $code, $type . "\\", $offset );
			if ( $addLength ) {
				$token .= substr( $code, $offset, $addLength );
				$offset += $addLength;
			} elseif ( $code[$offset] === '\\' ) {
				switch ( $code[$offset + 1] ) {
					case '\\':
						$token .= '\\';
						break;
					case $type:
						$token .= $type;
						break;
					case 'n':
						$token .= "\n";
						break;
					case 'r':
						$token .= "\r";
						break;
					case 't':
						$token .= "\t";
						break;
					case 'x':
						$chr = substr( $code, $offset + 2, 2 );

						if ( preg_match( '/^[0-9A-Fa-f]{2}$/', $chr ) ) {
							$token .= chr( hexdec( $chr ) );
							// \xXX -- 2 done later
							$offset += 2;
						} else {
							$token .= '\\x';
						}
						break;
					default:
						$token .= "\\" . $code[$offset + 1];
				}

				$offset += 2;

			} else {
				// Should never happen
				// @codeCoverageIgnoreStart
				$token .= $code[$offset];
				$offset++;
				// @codeCoverageIgnoreEnd
			}
		}
		throw new UserVisibleException( 'unclosedstring', $offset, [] );
	}
}
