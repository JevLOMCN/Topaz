<?php

namespace MediaWiki\Extension\ParserFunctions;

use Config;
use DateTime;
use DateTimeZone;
use Exception;
use LinkCache;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MWTimestamp;
use Parser;
use PPFrame;
use PPNode;
use RepoGroup;
use Sanitizer;
use StringUtils;
use StubObject;
use Title;
use Wikimedia\RequestTimeout\TimeoutException;

/**
 * Parser function handlers
 *
 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions
 */
class ParserFunctions {
	private static $mExprParser = null;
	private static $mTimeCache = [];
	private static $mTimeChars = 0;

	/** ~10 seconds */
	private const MAX_TIME_CHARS = 6000;

	/** @var Config */
	private $config;

	/** @var HookContainer */
	private $hookContainer;

	/** @var LanguageConverterFactory */
	private $languageConverterFactory;

	/** @var LanguageFactory */
	private $languageFactory;

	/** @var LanguageNameUtils */
	private $languageNameUtils;

	/** @var LinkCache */
	private $linkCache;

	/** @var RepoGroup */
	private $repoGroup;

	/** @var SpecialPageFactory */
	private $specialPageFactory;

	/**
	 * @param Config $config
	 * @param HookContainer $hookContainer
	 * @param LanguageConverterFactory $languageConverterFactory
	 * @param LanguageFactory $languageFactory
	 * @param LanguageNameUtils $languageNameUtils
	 * @param LinkCache $linkCache
	 * @param RepoGroup $repoGroup
	 * @param SpecialPageFactory $specialPageFactory
	 */
	public function __construct(
		Config $config,
		HookContainer $hookContainer,
		LanguageConverterFactory $languageConverterFactory,
		LanguageFactory $languageFactory,
		LanguageNameUtils $languageNameUtils,
		LinkCache $linkCache,
		RepoGroup $repoGroup,
		SpecialPageFactory $specialPageFactory
	) {
		$this->config = $config;
		$this->hookContainer = $hookContainer;
		$this->languageConverterFactory = $languageConverterFactory;
		$this->languageFactory = $languageFactory;
		$this->languageNameUtils = $languageNameUtils;
		$this->linkCache = $linkCache;
		$this->repoGroup = $repoGroup;
		$this->specialPageFactory = $specialPageFactory;
	}

	/**
	 * @return ExprParser
	 */
	private static function &getExprParser() {
		if ( self::$mExprParser === null ) {
			self::$mExprParser = new ExprParser;
		}
		return self::$mExprParser;
	}

	/**
	 * {{#expr: expression }}
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##expr
	 *
	 * @param Parser $parser
	 * @param string $expr
	 * @return string
	 */
	public function expr( Parser $parser, $expr = '' ) {
		try {
			return self::getExprParser()->doExpression( $expr );
		} catch ( ExprError $e ) {
			return '<strong class="error">' . htmlspecialchars( $e->getUserFriendlyMessage() ) . '</strong>';
		}
	}

	/**
	 * {{#ifexpr: expression | value if true | value if false }}
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##ifexpr
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string
	 */
	public function ifexpr( Parser $parser, PPFrame $frame, array $args ) {
		$expr = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$then = $args[1] ?? '';
		$else = $args[2] ?? '';

		try {
			$result = self::getExprParser()->doExpression( $expr );
			if ( is_numeric( $result ) ) {
				$result = (float)$result;
			}
			$result = $result ? $then : $else;
		} catch ( ExprError $e ) {
			return '<strong class="error">' . htmlspecialchars( $e->getUserFriendlyMessage() ) . '</strong>';
		}

		if ( is_object( $result ) ) {
			$result = trim( $frame->expand( $result ) );
		}

		return $result;
	}

	/**
	 * {{#if: test string | value if test string is not empty | value if test string is empty }}
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##if
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string
	 */
	public function if( Parser $parser, PPFrame $frame, array $args ) {
		$test = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		if ( $test !== '' ) {
			return isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		} else {
			return isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
		}
	}

	/**
	 * {{#ifeq: string 1 | string 2 | value if identical | value if different }}
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##ifeq
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string
	 */
	public function ifeq( Parser $parser, PPFrame $frame, array $args ) {
		$left = isset( $args[0] ) ? self::decodeTrimExpand( $args[0], $frame ) : '';
		$right = isset( $args[1] ) ? self::decodeTrimExpand( $args[1], $frame ) : '';

		// Strict compare is not possible here. 01 should equal 1 for example.
		/** @noinspection TypeUnsafeComparisonInspection */
		if ( $left == $right ) {
			return isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
		} else {
			return isset( $args[3] ) ? trim( $frame->expand( $args[3] ) ) : '';
		}
	}

	/**
	 * {{#iferror: test string | value if error | value if no error }}
	 *
	 * Error is when the input string contains an HTML object with class="error", as
	 * generated by other parser functions such as #expr, #time and #rel2abs.
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##iferror
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string
	 */
	public function iferror( Parser $parser, PPFrame $frame, array $args ) {
		$test = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$then = $args[1] ?? false;
		$else = $args[2] ?? false;

		if ( preg_match(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$test )
		) {
			$result = $then;
		} elseif ( $else === false ) {
			$result = $test;
		} else {
			$result = $else;
		}
		if ( $result === false ) {
			return '';
		}

		return trim( $frame->expand( $result ) );
	}

	/**
	 * {{#switch: comparison string
	 * | case = result
	 * | case = result
	 * | ...
	 * | default result
	 * }}
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##switch
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string
	 */
	public function switch( Parser $parser, PPFrame $frame, array $args ) {
		if ( count( $args ) === 0 ) {
			return '';
		}
		$primary = self::decodeTrimExpand( array_shift( $args ), $frame );
		$found = $defaultFound = false;
		$default = null;
		$lastItemHadNoEquals = false;
		$lastItem = '';
		$mwDefault = $parser->getMagicWordFactory()->get( 'default' );
		foreach ( $args as $arg ) {
			$bits = $arg->splitArg();
			$nameNode = $bits['name'];
			$index = $bits['index'];
			$valueNode = $bits['value'];

			if ( $index === '' ) {
				# Found "="
				$lastItemHadNoEquals = false;
				if ( $found ) {
					# Multiple input match
					return trim( $frame->expand( $valueNode ) );
				}
				$test = self::decodeTrimExpand( $nameNode, $frame );
				/** @noinspection TypeUnsafeComparisonInspection */
				if ( $test == $primary ) {
					# Found a match, return now
					return trim( $frame->expand( $valueNode ) );
				}
				if ( $defaultFound || $mwDefault->matchStartToEnd( $test ) ) {
					$default = $valueNode;
					$defaultFound = false;
				} # else wrong case, continue
			} else {
				# Multiple input, single output
				# If the value matches, set a flag and continue
				$lastItemHadNoEquals = true;
				// $lastItem is an "out" variable
				$decodedTest = self::decodeTrimExpand( $valueNode, $frame, $lastItem );
				/** @noinspection TypeUnsafeComparisonInspection */
				if ( $decodedTest == $primary ) {
					$found = true;
				} elseif ( $mwDefault->matchStartToEnd( $decodedTest ) ) {
					$defaultFound = true;
				}
			}
		}
		# Default case
		# Check if the last item had no = sign, thus specifying the default case
		if ( $lastItemHadNoEquals ) {
			return $lastItem;
		}
		if ( $default === null ) {
			return '';
		}
		return trim( $frame->expand( $default ) );
	}

	/**
	 * {{#rel2abs: path }} or {{#rel2abs: path | base path }}
	 *
	 * Returns the absolute path to a subpage, relative to the current article
	 * title. Treats titles as slash-separated paths.
	 *
	 * Following subpage link syntax instead of standard path syntax, an
	 * initial slash is treated as a relative path, and vice versa.
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##rel2abs
	 *
	 * @param Parser $parser
	 * @param string $to
	 * @param string $from
	 *
	 * @return string
	 */
	public function rel2abs( Parser $parser, $to = '', $from = '' ) {
		$from = trim( $from );
		if ( $from === '' ) {
			$from = $parser->getTitle()->getPrefixedText();
		}

		$to = rtrim( $to, ' /' );

		// if we have an empty path, or just one containing a dot
		if ( $to === '' || $to === '.' ) {
			return $from;
		}

		// if the path isn't relative
		if ( substr( $to, 0, 1 ) !== '/' &&
			substr( $to, 0, 2 ) !== './' &&
			substr( $to, 0, 3 ) !== '../' &&
			$to !== '..'
		) {
			$from = '';
		}
		// Make a long path, containing both, enclose it in /.../
		$fullPath = '/' . $from . '/' . $to . '/';

		// remove redundant current path dots
		$fullPath = preg_replace( '!/(\./)+!', '/', $fullPath );

		// remove double slashes
		$fullPath = preg_replace( '!/{2,}!', '/', $fullPath );

		// remove the enclosing slashes now
		$fullPath = trim( $fullPath, '/' );
		$exploded = explode( '/', $fullPath );
		$newExploded = [];

		foreach ( $exploded as $current ) {
			if ( $current === '..' ) { // removing one level
				if ( !count( $newExploded ) ) {
					// attempted to access a node above root node
					$msg = wfMessage( 'pfunc_rel2abs_invalid_depth', $fullPath )
						->inContentLanguage()->escaped();
					return '<strong class="error">' . $msg . '</strong>';
				}
				// remove last level from the stack
				array_pop( $newExploded );
			} else {
				// add the current level to the stack
				$newExploded[] = $current;
			}
		}

		// we can now join it again
		return implode( '/', $newExploded );
	}

	/**
	 * @param Parser $parser
	 * @param string $titletext
	 *
	 * @return bool
	 */
	private function ifexistInternal( Parser $parser, $titletext ): bool {
		$title = Title::newFromText( $titletext );
		$this->languageConverterFactory->getLanguageConverter( $parser->getContentLanguage() )
			->findVariantLink( $titletext, $title, true );
		if ( !$title ) {
			return false;
		}

		if ( $title->getNamespace() === NS_MEDIA ) {
			/* If namespace is specified as NS_MEDIA, then we want to
			 * check the physical file, not the "description" page.
			 */
			if ( !$parser->incrementExpensiveFunctionCount() ) {
				return false;
			}
			$file = $this->repoGroup->findFile( $title );
			if ( !$file ) {
				$parser->getOutput()->addImage(
					$title->getDBKey(), false, false );
				return false;
			}
			$parser->getOutput()->addImage(
				$file->getName(), $file->getTimestamp(), $file->getSha1() );
			return $file->exists();
		}
		if ( $title->isSpecialPage() ) {
			/* Don't bother with the count for special pages,
			 * since their existence can be checked without
			 * accessing the database.
			 */
			return $this->specialPageFactory->exists( $title->getDBkey() );
		}
		if ( $title->isExternal() ) {
			/* Can't check the existence of pages on other sites,
			 * so just return false.  Makes a sort of sense, since
			 * they don't exist _locally_.
			 */
			return false;
		}
		$pdbk = $title->getPrefixedDBkey();
		$id = $this->linkCache->getGoodLinkID( $pdbk );
		if ( $id !== 0 ) {
			$parser->getOutput()->addLink( $title, $id );
			return true;
		}
		if ( $this->linkCache->isBadLink( $pdbk ) ) {
			$parser->getOutput()->addLink( $title, 0 );
			return false;
		}
		if ( !$parser->incrementExpensiveFunctionCount() ) {
			return false;
		}
		$id = $title->getArticleID();
		$parser->getOutput()->addLink( $title, $id );

		// bug 70495: don't just check whether the ID != 0
		return $title->exists();
	}

	/**
	 * {{#ifexist: page title | value if exists | value if doesn't exist }}
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##ifexist
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string
	 */
	public function ifexist( Parser $parser, PPFrame $frame, array $args ) {
		$title = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$then = $args[1] ?? null;
		$else = $args[2] ?? null;

		$result = $this->ifexistInternal( $parser, $title ) ? $then : $else;
		if ( $result === null ) {
			return '';
		}
		return trim( $frame->expand( $result ) );
	}

	/**
	 * Used by time() and localTime()
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param string $format
	 * @param string $date
	 * @param string $language
	 * @param string|bool $local
	 * @return string
	 */
	private function timeCommon(
		Parser $parser, PPFrame $frame, $format, $date, $language, $local
	) {
		$this->hookContainer->register(
			'ParserClearState',
			static function () {
				self::$mTimeChars = 0;
			}
		);

		if ( $date === '' ) {
			$cacheKey = $parser->getOptions()->getTimestamp();
			$timestamp = new MWTimestamp( $cacheKey );
			$date = $timestamp->getTimestamp( TS_ISO_8601 );
			$useTTL = true;
		} else {
			$cacheKey = $date;
			$useTTL = false;
		}
		if ( isset( self::$mTimeCache[$format][$cacheKey][$language][$local] ) ) {
			$cachedVal = self::$mTimeCache[$format][$cacheKey][$language][$local];
			if ( $useTTL && $cachedVal[1] !== null ) {
				$frame->setTTL( $cachedVal[1] );
			}
			return $cachedVal[0];
		}

		# compute the timestamp string $ts
		# PHP >= 5.2 can handle dates before 1970 or after 2038 using the DateTime object

		$invalidTime = false;

		# the DateTime constructor must be used because it throws exceptions
		# when errors occur, whereas date_create appears to just output a warning
		# that can't really be detected from within the code
		try {

			# Default input timezone is UTC.
			$utc = new DateTimeZone( 'UTC' );

			# Correct for DateTime interpreting 'XXXX' as XX:XX o'clock
			if ( preg_match( '/^[0-9]{4}$/', $date ) ) {
				$date = '00:00 ' . $date;
			}

			# Parse date
			# UTC is a default input timezone.
			$dateObject = new DateTime( $date, $utc );

			# Set output timezone.
			if ( $local ) {
				$tz = new DateTimeZone(
					$this->config->get( 'Localtimezone' ) ??
					date_default_timezone_get()
				);
			} else {
				$tz = $utc;
			}
			$dateObject->setTimezone( $tz );
			# Generate timestamp
			$ts = $dateObject->format( 'YmdHis' );

		} catch ( TimeoutException $ex ) {
			// Unfortunately DateTime throws a generic Exception, but we can't
			// ignore an exception generated by the RequestTimeout library.
			throw $ex;
		} catch ( Exception $ex ) {
			$invalidTime = true;
		}

		$ttl = null;
		# format the timestamp and return the result
		if ( $invalidTime ) {
			$result = '<strong class="error">' .
				wfMessage( 'pfunc_time_error' )->inContentLanguage()->escaped() .
				'</strong>';
		} else {
			self::$mTimeChars += strlen( $format );
			if ( self::$mTimeChars > self::MAX_TIME_CHARS ) {
				return '<strong class="error">' .
					wfMessage( 'pfunc_time_too_long' )->inContentLanguage()->escaped() .
					'</strong>';
			}

			if ( $ts < 0 ) { // Language can't deal with BC years
				return '<strong class="error">' .
					wfMessage( 'pfunc_time_too_small' )->inContentLanguage()->escaped() .
					'</strong>';
			}
			if ( $ts >= 100000000000000 ) { // Language can't deal with years after 9999
				return '<strong class="error">' .
					wfMessage( 'pfunc_time_too_big' )->inContentLanguage()->escaped() .
					'</strong>';
			}

			if ( $language !== '' && $this->languageNameUtils->isValidBuiltInCode( $language ) ) {
				// use whatever language is passed as a parameter
				$langObject = $this->languageFactory->getLanguage( $language );
			} else {
				// use wiki's content language
				$langObject = $parser->getTargetLanguage();
				// $ttl is passed by reference, which doesn't work right on stub objects
				StubObject::unstub( $langObject );
			}
			$result = $langObject->sprintfDate( $format, $ts, $tz, $ttl );
		}
		self::$mTimeCache[$format][$cacheKey][$language][$local] = [ $result, $ttl ];
		if ( $useTTL && $ttl !== null ) {
			$frame->setTTL( $ttl );
		}
		return $result;
	}

	/**
	 * {{#time: format string }}
	 * {{#time: format string | date/time object }}
	 * {{#time: format string | date/time object | language code }}
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##time
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string
	 */
	public function time( Parser $parser, PPFrame $frame, array $args ) {
		$format = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$date = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		$language = isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
		$local = isset( $args[3] ) && trim( $frame->expand( $args[3] ) );
		return $this->timeCommon( $parser, $frame, $format, $date, $language, $local );
	}

	/**
	 * {{#timel: ... }}
	 *
	 * Identical to {{#time: ... }}, except that it uses the local time of the wiki
	 * (as set in $wgLocaltimezone) when no date is given.
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##timel
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string
	 */
	public function localTime( Parser $parser, PPFrame $frame, array $args ) {
		$format = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$date = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		$language = isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
		return $this->timeCommon( $parser, $frame, $format, $date, $language, true );
	}

	/**
	 * Obtain a specified number of slash-separated parts of a title,
	 * e.g. {{#titleparts:Hello/World|1}} => "Hello"
	 *
	 * @link https://www.mediawiki.org/wiki/Help:Extension:ParserFunctions##titleparts
	 *
	 * @param Parser $parser Parent parser
	 * @param string $title Title to split
	 * @param string|int $parts Number of parts to keep
	 * @param string|int $offset Offset starting at 1
	 * @return string
	 */
	public function titleparts( Parser $parser, $title = '', $parts = 0, $offset = 0 ) {
		$parts = (int)$parts;
		$offset = (int)$offset;
		$ntitle = Title::newFromText( $title );
		if ( !$ntitle ) {
			return $title;
		}

		$bits = explode( '/', $ntitle->getPrefixedText(), 25 );
		if ( $offset > 0 ) {
			--$offset;
		}
		return implode( '/', array_slice( $bits, $offset, $parts ?: null ) );
	}

	/**
	 * Verifies parameter is less than max string length.
	 *
	 * @param string $text
	 * @return bool
	 */
	private function checkLength( $text ) {
		return ( mb_strlen( $text ) < $this->config->get( 'PFStringLengthLimit' ) );
	}

	/**
	 * Generates error message. Called when string is too long.
	 * @return string
	 */
	private function tooLongError() {
		$msg = wfMessage( 'pfunc_string_too_long' )
			->numParams( $this->config->get( 'PFStringLengthLimit' ) );
		return '<strong class="error">' . $msg->inContentLanguage()->escaped() . '</strong>';
	}

	/**
	 * {{#len:string}}
	 *
	 * Reports number of characters in string.
	 *
	 * @param Parser $parser
	 * @param string $inStr
	 * @return int
	 */
	public function runLen( Parser $parser, $inStr = '' ) {
		$inStr = $parser->killMarkers( (string)$inStr );
		return mb_strlen( $inStr );
	}

	/**
	 * {{#pos: string | needle | offset}}
	 *
	 * Finds first occurrence of "needle" in "string" starting at "offset".
	 *
	 * Note: If the needle is an empty string, single space is used instead.
	 * Note: If the needle is not found, empty string is returned.
	 * @param Parser $parser
	 * @param string $inStr
	 * @param string $inNeedle
	 * @param string|int $inOffset
	 * @return int|string
	 */
	public function runPos( Parser $parser, $inStr = '', $inNeedle = '', $inOffset = 0 ) {
		$inStr = $parser->killMarkers( (string)$inStr );
		$inNeedle = $parser->killMarkers( (string)$inNeedle );

		if ( !$this->checkLength( $inStr ) ||
			!$this->checkLength( $inNeedle ) ) {
			return $this->tooLongError();
		}

		if ( $inNeedle === '' ) {
			$inNeedle = ' ';
		}

		$pos = mb_strpos( $inStr, $inNeedle, min( (int)$inOffset, mb_strlen( $inStr ) ) );
		if ( $pos === false ) {
			$pos = '';
		}

		return $pos;
	}

	/**
	 * {{#rpos: string | needle}}
	 *
	 * Finds last occurrence of "needle" in "string".
	 *
	 * Note: If the needle is an empty string, single space is used instead.
	 * Note: If the needle is not found, -1 is returned.
	 * @param Parser $parser
	 * @param string $inStr
	 * @param string $inNeedle
	 * @return int|string
	 */
	public function runRPos( Parser $parser, $inStr = '', $inNeedle = '' ) {
		$inStr = $parser->killMarkers( (string)$inStr );
		$inNeedle = $parser->killMarkers( (string)$inNeedle );

		if ( !$this->checkLength( $inStr ) ||
			!$this->checkLength( $inNeedle ) ) {
			return $this->tooLongError();
		}

		if ( $inNeedle === '' ) {
			$inNeedle = ' ';
		}

		$pos = mb_strrpos( $inStr, $inNeedle );
		if ( $pos === false ) {
			$pos = -1;
		}

		return $pos;
	}

	/**
	 * {{#sub: string | start | length }}
	 *
	 * Returns substring of "string" starting at "start" and having
	 * "length" characters.
	 *
	 * Note: If length is zero, the rest of the input is returned.
	 * Note: A negative value for "start" operates from the end of the
	 *   "string".
	 * Note: A negative value for "length" returns a string reduced in
	 *   length by that amount.
	 *
	 * @param Parser $parser
	 * @param string $inStr
	 * @param string|int $inStart
	 * @param string|int $inLength
	 * @return string
	 */
	public function runSub( Parser $parser, $inStr = '', $inStart = 0, $inLength = 0 ) {
		$inStr = $parser->killMarkers( (string)$inStr );

		if ( !$this->checkLength( $inStr ) ) {
			return $this->tooLongError();
		}

		if ( (int)$inLength === 0 ) {
			$result = mb_substr( $inStr, (int)$inStart );
		} else {
			$result = mb_substr( $inStr, (int)$inStart, (int)$inLength );
		}

		return $result;
	}

	/**
	 * {{#count: string | substr }}
	 *
	 * Returns number of occurrences of "substr" in "string".
	 *
	 * Note: If "substr" is empty, a single space is used.
	 *
	 * @param Parser $parser
	 * @param string $inStr
	 * @param string $inSubStr
	 * @return int|string
	 */
	public function runCount( Parser $parser, $inStr = '', $inSubStr = '' ) {
		$inStr = $parser->killMarkers( (string)$inStr );
		$inSubStr = $parser->killMarkers( (string)$inSubStr );

		if ( !$this->checkLength( $inStr ) ||
			!$this->checkLength( $inSubStr ) ) {
			return $this->tooLongError();
		}

		if ( $inSubStr === '' ) {
			$inSubStr = ' ';
		}

		$result = mb_substr_count( $inStr, $inSubStr );

		return $result;
	}

	/**
	 * {{#replace:string | from | to | limit }}
	 *
	 * Replaces each occurrence of "from" in "string" with "to".
	 * At most "limit" replacements are performed.
	 *
	 * Note: Armored against replacements that would generate huge strings.
	 * Note: If "from" is an empty string, single space is used instead.
	 *
	 * @param Parser $parser
	 * @param string $inStr
	 * @param string $inReplaceFrom
	 * @param string $inReplaceTo
	 * @param string|int $inLimit
	 * @return string
	 */
	public function runReplace( Parser $parser, $inStr = '',
			$inReplaceFrom = '', $inReplaceTo = '', $inLimit = -1 ) {
		$inStr = $parser->killMarkers( (string)$inStr );
		$inReplaceFrom = $parser->killMarkers( (string)$inReplaceFrom );
		$inReplaceTo = $parser->killMarkers( (string)$inReplaceTo );

		if ( !$this->checkLength( $inStr ) ||
			!$this->checkLength( $inReplaceFrom ) ||
			!$this->checkLength( $inReplaceTo ) ) {
			return $this->tooLongError();
		}

		if ( $inReplaceFrom === '' ) {
			$inReplaceFrom = ' ';
		}

		// Precompute limit to avoid generating enormous string:
		$diff = mb_strlen( $inReplaceTo ) - mb_strlen( $inReplaceFrom );
		if ( $diff > 0 ) {
			$limit = (int)( ( $this->config->get( 'PFStringLengthLimit' ) - mb_strlen( $inStr ) ) / $diff ) + 1;
		} else {
			$limit = -1;
		}

		$inLimit = (int)$inLimit;
		if ( $inLimit >= 0 ) {
			if ( $limit > $inLimit || $limit == -1 ) {
				$limit = $inLimit;
			}
		}

		// Use regex to allow limit and handle UTF-8 correctly.
		$inReplaceFrom = preg_quote( $inReplaceFrom, '/' );
		$inReplaceTo = StringUtils::escapeRegexReplacement( $inReplaceTo );

		$result = preg_replace( '/' . $inReplaceFrom . '/u',
						$inReplaceTo, $inStr, $limit );

		if ( !$this->checkLength( $result ) ) {
			return $this->tooLongError();
		}

		return $result;
	}

	/**
	 * {{#explode:string | delimiter | position | limit}}
	 *
	 * Breaks "string" into chunks separated by "delimiter" and returns the
	 * chunk identified by "position".
	 *
	 * Note: Negative position can be used to specify tokens from the end.
	 * Note: If the divider is an empty string, single space is used instead.
	 * Note: Empty string is returned if there are not enough exploded chunks.
	 *
	 * @param Parser $parser
	 * @param string $inStr
	 * @param string $inDiv
	 * @param string|int $inPos
	 * @param string|null $inLim
	 * @return string
	 */
	public function runExplode(
		Parser $parser, $inStr = '', $inDiv = '', $inPos = 0, $inLim = null
	) {
		$inStr = $parser->killMarkers( (string)$inStr );
		$inDiv = $parser->killMarkers( (string)$inDiv );

		if ( $inDiv === '' ) {
			$inDiv = ' ';
		}

		if ( !$this->checkLength( $inStr ) ||
			!$this->checkLength( $inDiv ) ) {
			return $this->tooLongError();
		}

		$inDiv = preg_quote( $inDiv, '/' );

		$matches = preg_split( '/' . $inDiv . '/u', $inStr, (int)$inLim );

		if ( $inPos >= 0 && isset( $matches[$inPos] ) ) {
			$result = $matches[$inPos];
		} elseif ( $inPos < 0 && isset( $matches[count( $matches ) + $inPos] ) ) {
			$result = $matches[count( $matches ) + $inPos];
		} else {
			$result = '';
		}

		return $result;
	}

	/**
	 * {{#urldecode:string}}
	 *
	 * Decodes URL-encoded (like%20that) strings.
	 *
	 * @param Parser $parser
	 * @param string $inStr
	 * @return string
	 */
	public function runUrlDecode( Parser $parser, $inStr = '' ) {
		$inStr = $parser->killMarkers( (string)$inStr );
		if ( !$this->checkLength( $inStr ) ) {
			return $this->tooLongError();
		}

		return urldecode( $inStr );
	}

	/**
	 * Take a PPNode (-ish thing), expand it, remove entities, and trim.
	 *
	 * For use when doing string comparisions, where user expects entities
	 * to be equal for what they stand for (e.g. comparisions with {{PAGENAME}})
	 *
	 * @param PPNode|string $obj Thing to expand
	 * @param PPFrame $frame
	 * @param string &$trimExpanded @phan-output-reference Expanded and trimmed version of PPNode,
	 *   but with char refs intact
	 * @return string The trimmed, expanded and entity reference decoded version of the PPNode
	 */
	private static function decodeTrimExpand( $obj, PPFrame $frame, &$trimExpanded = '' ) {
		$expanded = $frame->expand( $obj );
		$trimExpanded = trim( $expanded );
		return trim( Sanitizer::decodeCharReferences( $expanded ) );
	}
}
