<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use DateTime;
use DateTimeZone;
use Exception;
use Language;
use LanguageCode;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\Utils\MWTimestamp;
use Wikimedia\RequestTimeout\TimeoutException;

class LanguageLibrary extends LibraryBase {
	/** @var Language[] */
	public $langCache = [];
	/** @var array */
	public $timeCache = [];
	/** @var int */
	public $maxLangCacheSize;

	public function register() {
		// Pre-populate the language cache
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();
		$this->langCache[$contLang->getCode()] = $contLang;
		$this->maxLangCacheSize = $this->getEngine()->getOption( 'maxLangCacheSize' );

		$statics = [
			'getContLangCode',
			'isSupportedLanguage',
			'isKnownLanguageTag',
			'isValidCode',
			'isValidBuiltInCode',
			'fetchLanguageName',
			'fetchLanguageNames',
			'getFallbacksFor',
			'toBcp47Code',
		];
		$methods = [
			'lcfirst',
			'ucfirst',
			'lc',
			'uc',
			'caseFold',
			'formatNum',
			'formatDate',
			'formatDuration',
			'getDurationIntervals',
			'parseFormattedNumber',
			'convertPlural',
			'convertGrammar',
			'gender',
			'isRTL',
		];
		$lib = [];
		foreach ( $statics as $name ) {
			$lib[$name] = [ $this, $name ];
		}
		foreach ( $methods as $name ) {
			$lib[$name] = function () use ( $name ) {
				$args = func_get_args();
				return $this->languageMethod( $name, $args );
			};
		}
		return $this->getEngine()->registerInterface( 'mw.language.lua', $lib );
	}

	/**
	 * Handler for getContLangCode
	 * @internal
	 * @return string[]
	 */
	public function getContLangCode() {
		return [ MediaWikiServices::getInstance()->getContentLanguage()->getCode() ];
	}

	/**
	 * Handler for isSupportedLanguage
	 * @internal
	 * @param string $code
	 * @return bool[]
	 */
	public function isSupportedLanguage( $code ) {
		$this->checkType( 'isSupportedLanguage', 1, $code, 'string' );
		return [ MediaWikiServices::getInstance()->getLanguageNameUtils()->isSupportedLanguage( $code ) ];
	}

	/**
	 * Handler for isKnownLanguageTag
	 * @internal
	 * @param string $code
	 * @return bool[]
	 */
	public function isKnownLanguageTag( $code ) {
		$this->checkType( 'isKnownLanguageTag', 1, $code, 'string' );
		return [ MediaWikiServices::getInstance()->getLanguageNameUtils()->isKnownLanguageTag( $code ) ];
	}

	/**
	 * Handler for isValidCode
	 * @internal
	 * @param string $code
	 * @return bool[]
	 */
	public function isValidCode( $code ) {
		$this->checkType( 'isValidCode', 1, $code, 'string' );
		return [ MediaWikiServices::getInstance()->getLanguageNameUtils()->isValidCode( $code ) ];
	}

	/**
	 * Handler for isValidBuiltInCode
	 * @internal
	 * @param string $code
	 * @return bool[]
	 */
	public function isValidBuiltInCode( $code ) {
		$this->checkType( 'isValidBuiltInCode', 1, $code, 'string' );
		return [ MediaWikiServices::getInstance()->getLanguageNameUtils()->isValidBuiltInCode( $code ) ];
	}

	/**
	 * Handler for fetchLanguageName
	 * @internal
	 * @param string $code
	 * @param null|string $inLanguage
	 * @return string[]
	 */
	public function fetchLanguageName( $code, $inLanguage ) {
		$this->checkType( 'fetchLanguageName', 1, $code, 'string' );
		$this->checkTypeOptional( 'fetchLanguageName', 2, $inLanguage, 'string', LanguageNameUtils::AUTONYMS );
		return [ MediaWikiServices::getInstance()->getLanguageNameUtils()
			->getLanguageName( $code, $inLanguage ) ];
	}

	/**
	 * Handler for fetchLanguageNames
	 * @internal
	 * @param null|string $inLanguage
	 * @param null|string $include
	 * @return string[][]
	 */
	public function fetchLanguageNames( $inLanguage, $include ) {
		$this->checkTypeOptional( 'fetchLanguageNames', 1, $inLanguage, 'string', LanguageNameUtils::AUTONYMS );
		$this->checkTypeOptional( 'fetchLanguageNames', 2, $include, 'string', LanguageNameUtils::DEFINED );
		return [ MediaWikiServices::getInstance()->getLanguageNameUtils()
			->getLanguageNames( $inLanguage, $include ) ];
	}

	/**
	 * Handler for fetchLanguageNames
	 * @internal
	 * @param string $code
	 * @return string[][]
	 */
	public function getFallbacksFor( $code ) {
		$this->checkType( 'getFallbacksFor', 1, $code, 'string' );
		$ret = MediaWikiServices::getInstance()->getLanguageFallback()->getAll( $code );
		// Make 1-based
		if ( count( $ret ) ) {
			$ret = array_combine( range( 1, count( $ret ) ), $ret );
		}
		return [ $ret ];
	}

	/**
	 * Handler for toBcp47Code
	 * @internal
	 * @param string $code a MediaWiki-internal code
	 * @return string[] a BCP-47 language tag
	 */
	public function toBcp47Code( $code ) {
		$this->checkType( 'toBcp47Code', 1, $code, 'string' );
		$ret = LanguageCode::bcp47( $code );
		return [ $ret ];
	}

	/**
	 * Language object method handler
	 * @internal
	 * @param string $name
	 * @param array $args
	 * @return array
	 * @throws LuaError
	 */
	public function languageMethod( $name, $args ) {
		$name = strval( $name );
		$code = array_shift( $args );
		if ( !isset( $this->langCache[$code] ) ) {
			if ( count( $this->langCache ) > $this->maxLangCacheSize ) {
				throw new LuaError( 'too many language codes requested' );
			}
			$services = MediaWikiServices::getInstance();
			if ( $services->getLanguageNameUtils()->isValidCode( $code ) ) {
				$this->langCache[$code] = $services->getLanguageFactory()->getLanguage( $code );
			} else {
				throw new LuaError( "language code '$code' is invalid" );
			}
		}
		$lang = $this->langCache[$code];
		switch ( $name ) {
			// Zero arguments
			case 'isRTL':
				return [ $lang->$name() ];

			// One string argument passed straight through
			case 'lcfirst':
			case 'ucfirst':
			case 'lc':
			case 'uc':
			case 'caseFold':
				$this->checkType( $name, 1, $args[0], 'string' );
				return [ $lang->$name( $args[0] ) ];

			case 'parseFormattedNumber':
				if ( is_numeric( $args[0] ) ) {
					$args[0] = strval( $args[0] );
				}
				if ( $this->getLuaType( $args[0] ) !== 'string' ) {
					// Be like tonumber(), return nil instead of erroring out
					return [ null ];
				}
				return [ $lang->$name( $args[0] ) ];

			// Custom handling
			default:
				return $this->$name( $lang, $args );
		}
	}

	/**
	 * convertPlural handler
	 * @internal
	 * @param Language $lang
	 * @param array $args
	 * @return array
	 */
	public function convertPlural( $lang, $args ) {
		$number = array_shift( $args );
		$this->checkType( 'convertPlural', 1, $number, 'number' );
		if ( is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$forms = array_values( array_map( 'strval', $args ) );
		return [ $lang->convertPlural( $number, $forms ) ];
	}

	/**
	 * convertGrammar handler
	 * @internal
	 * @param Language $lang
	 * @param array $args
	 * @return array
	 */
	public function convertGrammar( $lang, $args ) {
		$this->checkType( 'convertGrammar', 1, $args[0], 'string' );
		$this->checkType( 'convertGrammar', 2, $args[1], 'string' );
		return [ $lang->convertGrammar( $args[0], $args[1] ) ];
	}

	/**
	 * gender handler
	 * @internal
	 * @param Language $lang
	 * @param array $args
	 * @return array
	 */
	public function gender( $lang, $args ) {
		$this->checkType( 'gender', 1, $args[0], 'string' );
		$username = trim( array_shift( $args ) );

		if ( is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$forms = array_values( array_map( 'strval', $args ) );

		// Shortcuts
		if ( count( $forms ) === 0 ) {
			return [ '' ];
		} elseif ( count( $forms ) === 1 ) {
			return [ $forms[0] ];
		}

		if ( $username === 'male' || $username === 'female' ) {
			$gender = $username;
		} else {
			$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
			// default
			$gender = $userOptionsLookup->getDefaultOption( 'gender' );

			// Check for "User:" prefix
			$title = Title::newFromText( $username );
			if ( $title && $title->getNamespace() === NS_USER ) {
				$username = $title->getText();
			}

			// check parameter, or use the ParserOptions if in interface message
			$user = User::newFromName( $username );
			if ( $user ) {
				$genderCache = MediaWikiServices::getInstance()->getGenderCache();
				$gender = $genderCache->getGenderOf( $user, __METHOD__ );
			} elseif ( $username === '' ) {
				$parserOptions = $this->getParserOptions();
				if ( $parserOptions->getInterfaceMessage() ) {
					$genderCache = MediaWikiServices::getInstance()->getGenderCache();
					$gender = $genderCache->getGenderOf( $parserOptions->getUserIdentity(), __METHOD__ );
				}
			}
		}
		return [ $lang->gender( $gender, $forms ) ];
	}

	/**
	 * formatNum handler
	 * @internal
	 * @param Language $lang
	 * @param array $args
	 * @return array
	 */
	public function formatNum( $lang, $args ) {
		$num = $args[0];
		$this->checkType( 'formatNum', 1, $num, 'number' );
		if ( is_infinite( $num ) ) {
			throw new LuaError( "bad argument #1 to 'formatNum' (infinite)" );
		}
		if ( is_nan( $num ) ) {
			throw new LuaError( "bad argument #1 to 'formatNum' (NaN)" );
		}

		$noCommafy = false;
		if ( isset( $args[1] ) ) {
			$this->checkType( 'formatNum', 2, $args[1], 'table' );
			$options = $args[1];
			$noCommafy = !empty( $options['noCommafy'] );
		}
		if ( $noCommafy ) {
			return [ $lang->formatNumNoSeparators( $num ) ];
		} else {
			return [ $lang->formatNum( $num ) ];
		}
	}

	/**
	 * formatDate handler
	 * @internal
	 * @param Language $lang
	 * @param array $args
	 * @return array
	 * @throws LuaError
	 */
	public function formatDate( $lang, $args ) {
		$this->checkType( 'formatDate', 1, $args[0], 'string' );
		$this->checkTypeOptional( 'formatDate', 2, $args[1], 'string', '' );
		$this->checkTypeOptional( 'formatDate', 3, $args[2], 'boolean', false );

		[ $format, $date, $local ] = $args;
		$langcode = $lang->getCode();

		if ( $date === '' ) {
			$cacheKey = $this->getParserOptions()->getTimestamp();
			$timestamp = new MWTimestamp( $cacheKey );
			$date = $timestamp->getTimestamp( TS_ISO_8601 );
			$useTTL = true;
		} else {
			# Correct for DateTime interpreting 'XXXX' as XX:XX o'clock
			if ( preg_match( '/^[0-9]{4}$/', $date ) ) {
				$date = '00:00 ' . $date;
			}

			$cacheKey = $date;
			$useTTL = false;
		}

		if ( isset( $this->timeCache[$format][$cacheKey][$langcode][$local] ) ) {
			$ttl = $this->timeCache[$format][$cacheKey][$langcode][$local][1];
			if ( $useTTL && $ttl !== null ) {
				$this->getEngine()->setTTL( $ttl );
			}
			return [ $this->timeCache[$format][$cacheKey][$langcode][$local][0] ];
		}

		# Default input timezone is UTC.
		try {
			$utc = new DateTimeZone( 'UTC' );
			$dateObject = new DateTime( $date, $utc );
		} catch ( TimeoutException $ex ) {
			// Unfortunately DateTime throws a generic Exception, but we can't
			// ignore an exception generated by the RequestTimeout library.
			throw $ex;
		} catch ( Exception $ex ) {
			throw new LuaError( "bad argument #2 to 'formatDate': invalid timestamp '$date'" );
		}

		# Set output timezone.
		if ( $local ) {
			$localtimezone = MediaWikiServices::getInstance()->getMainConfig()->get( MainConfigNames::Localtimezone );
			if ( isset( $localtimezone ) ) {
				$tz = new DateTimeZone( $localtimezone );
			} else {
				$tz = new DateTimeZone( date_default_timezone_get() );
			}
		} else {
			$tz = $utc;
		}
		$dateObject->setTimezone( $tz );
		# Generate timestamp
		$ts = $dateObject->format( 'YmdHis' );

		if ( $ts < 0 ) {
			throw new LuaError( "mw.language:formatDate() only supports years from 0" );
		} elseif ( $ts >= 100000000000000 ) {
			throw new LuaError( "mw.language:formatDate() only supports years up to 9999" );
		}

		$ttl = null;
		$ret = $lang->sprintfDate( $format, $ts, $tz, $ttl );
		$this->timeCache[$format][$cacheKey][$langcode][$local] = [ $ret, $ttl ];
		if ( $useTTL && $ttl !== null ) {
			$this->getEngine()->setTTL( $ttl );
		}
		return [ $ret ];
	}

	/**
	 * formatDuration handler
	 * @internal
	 * @param Language $lang
	 * @param array $args
	 * @return array
	 */
	public function formatDuration( $lang, $args ) {
		$this->checkType( 'formatDuration', 1, $args[0], 'number' );
		$this->checkTypeOptional( 'formatDuration', 2, $args[1], 'table', [] );

		[ $seconds, $chosenIntervals ] = $args;
		$chosenIntervals = array_values( $chosenIntervals );

		$ret = $lang->formatDuration( $seconds, $chosenIntervals );
		return [ $ret ];
	}

	/**
	 * getDurationIntervals handler
	 * @internal
	 * @param Language $lang
	 * @param array $args
	 * @return array
	 */
	public function getDurationIntervals( $lang, $args ) {
		$this->checkType( 'getDurationIntervals', 1, $args[0], 'number' );
		$this->checkTypeOptional( 'getDurationIntervals', 2, $args[1], 'table', [] );

		[ $seconds, $chosenIntervals ] = $args;
		$chosenIntervals = array_values( $chosenIntervals );

		$ret = $lang->getDurationIntervals( $seconds, $chosenIntervals );
		return [ $ret ];
	}
}
