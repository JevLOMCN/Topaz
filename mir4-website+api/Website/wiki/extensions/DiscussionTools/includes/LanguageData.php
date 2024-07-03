<?php
/**
 * Generates language-specific data used by DiscussionTools.
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools;

use DateTimeZone;
use ILanguageConverter;
use Language;
use MediaWiki\Config\Config;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\MainConfigNames;
use MediaWiki\SpecialPage\SpecialPageFactory;

class LanguageData {

	private Config $config;
	private Language $language;
	private LanguageConverterFactory $languageConverterFactory;
	private SpecialPageFactory $specialPageFactory;

	public function __construct(
		Config $config,
		Language $language,
		LanguageConverterFactory $languageConverterFactory,
		SpecialPageFactory $specialPageFactory
	) {
		$this->config = $config;
		$this->language = $language;
		$this->languageConverterFactory = $languageConverterFactory;
		$this->specialPageFactory = $specialPageFactory;
	}

	/**
	 * Compute data we need to parse discussion threads on pages.
	 */
	public function getLocalData(): array {
		$config = $this->config;
		$lang = $this->language;
		$langConv = $this->languageConverterFactory->getLanguageConverter( $lang );

		$data = [];

		$data['dateFormat'] = [];
		$dateFormat = $lang->getDateFormatString( 'both', $lang->dateFormat( false ) );
		foreach ( $langConv->getVariants() as $variant ) {
			$convDateFormat = $this->convertDateFormat( $dateFormat, $langConv, $variant );
			$data['dateFormat'][$variant] = $convDateFormat;
		}

		$data['digits'] = [];
		foreach ( $langConv->getVariants() as $variant ) {
			$data['digits'][$variant] = [];
			foreach ( str_split( '0123456789' ) as $digit ) {
				if ( $config->get( MainConfigNames::TranslateNumerals ) ) {
					$localDigit = $lang->formatNumNoSeparators( $digit );
				} else {
					$localDigit = $digit;
				}
				$convLocalDigit = $langConv->translate( $localDigit, $variant );
				$data['digits'][$variant][] = $convLocalDigit;
			}
		}

		// ApiQuerySiteinfo
		$data['localTimezone'] = $config->get( MainConfigNames::Localtimezone );

		// special page names compared against Title::getText, which contains space
		// But aliases are stored with underscores (db key) in the alias files
		$data['specialContributionsName'] = str_replace( '_', ' ', $this->specialPageFactory
			->getLocalNameFor( 'Contributions' ) );
		$data['specialNewSectionName'] = str_replace( '_', ' ', $this->specialPageFactory
			->getLocalNameFor( 'NewSection' ) );

		$localTimezone = $config->get( MainConfigNames::Localtimezone );
		// Return all timezone abbreviations for the local timezone (there will often be two, for
		// non-DST and DST timestamps, and sometimes more due to historical data, but that's okay).
		// Avoid DateTimeZone::listAbbreviations(), it returns some half-baked list that is different
		// from the timezone data used by everything else in PHP.
		$timezoneTransitions = ( new DateTimeZone( $localTimezone ) )->getTransitions();
		if ( !is_array( $timezoneTransitions ) ) {
			// Handle (arguably invalid) config where $wgLocaltimezone is an abbreviation like "CST"
			// instead of a real IANA timezone name like "America/Chicago". (T312310)
			// "DateTimeZone objects wrapping type 1 (UTC offsets) and type 2 (abbreviations) do not
			// contain any transitions, and calling this method on them will return false."
			// https://www.php.net/manual/en/datetimezone.gettransitions.php
			$timezoneAbbrs = [ $localTimezone ];
		} else {
			$timezoneAbbrs = array_values( array_unique(
				array_map( static function ( $transition ) {
					return $transition['abbr'];
				}, $timezoneTransitions )
			) );
		}

		$data['timezones'] = [];
		foreach ( $langConv->getVariants() as $variant ) {
			$data['timezones'][$variant] = array_combine(
				array_map( static function ( string $tzMsg ) use ( $lang, $langConv, $variant ) {
					// MWTimestamp::getTimezoneMessage()
					// Parser::pstPass2()
					// Messages used here: 'timezone-utc' and so on
					$key = 'timezone-' . strtolower( trim( $tzMsg ) );
					$msg = wfMessage( $key )->inLanguage( $lang );
					// TODO: This probably causes a similar issue to https://phabricator.wikimedia.org/T221294,
					// but we *must* check the message existence in the database, because the messages are not
					// actually defined by MediaWiki core for any timezone other than UTC...
					if ( $msg->exists() ) {
						$text = $msg->text();
					} else {
						$text = strtoupper( $tzMsg );
					}
					$convText = $langConv->translate( $text, $variant );
					return $convText;
				}, $timezoneAbbrs ),
				array_map( 'strtoupper', $timezoneAbbrs )
			);
		}

		// Messages in content language
		$messagesKeys = array_merge(
			Language::WEEKDAY_MESSAGES,
			Language::WEEKDAY_ABBREVIATED_MESSAGES,
			Language::MONTH_MESSAGES,
			Language::MONTH_GENITIVE_MESSAGES,
			Language::MONTH_ABBREVIATED_MESSAGES
		);
		$data['contLangMessages'] = [];
		foreach ( $langConv->getVariants() as $variant ) {
			$data['contLangMessages'][$variant] = array_combine(
				$messagesKeys,
				array_map( static function ( $key ) use ( $lang, $langConv, $variant ) {
					$text = wfMessage( $key )->inLanguage( $lang )->text();
					return $langConv->translate( $text, $variant );
				}, $messagesKeys )
			);
		}

		return $data;
	}

	/**
	 * Convert a date format string to a different language variant, leaving all special characters
	 * unchanged and applying language conversion to the plain text fragments.
	 */
	private function convertDateFormat(
		string $format,
		ILanguageConverter $langConv,
		string $variant
	): string {
		$formatLength = strlen( $format );
		$s = '';
		// The supported codes must match CommentParser::getTimestampRegexp()
		for ( $p = 0; $p < $formatLength; $p++ ) {
			$num = false;
			$code = $format[ $p ];
			if ( $code === 'x' && $p < $formatLength - 1 ) {
				$code .= $format[++$p];
			}
			if ( $code === 'xk' && $p < $formatLength - 1 ) {
				$code .= $format[++$p];
			}

			// LAZY SHORTCUTS that might cause bugs:
			// * We assume that result of $langConv->translate() doesn't produce any special codes/characters
			// * We assume that calling $langConv->translate() separately for each character is correct
			switch ( $code ) {
				case 'xx':
				case 'xg':
				case 'xn':
				case 'd':
				case 'D':
				case 'j':
				case 'l':
				case 'F':
				case 'M':
				case 'm':
				case 'n':
				case 'Y':
				case 'xkY':
				case 'G':
				case 'H':
				case 'i':
				case 's':
					// Special code - pass through unchanged
					$s .= $code;
					break;
				case '\\':
					// Plain text (backslash escaping) - convert to language variant
					if ( $p < $formatLength - 1 ) {
						$s .= '\\' . $langConv->translate( $format[++$p], $variant );
					} else {
						$s .= $code;
					}
					break;
				case '"':
					// Plain text (quoted literal) - convert to language variant
					if ( $p < $formatLength - 1 ) {
						$endQuote = strpos( $format, '"', $p + 1 );
						if ( $endQuote === false ) {
							// No terminating quote, assume literal "
							$s .= $code;
						} else {
							$s .= '"' .
								$langConv->translate( substr( $format, $p + 1, $endQuote - $p - 1 ), $variant ) .
								'"';
							$p = $endQuote;
						}
					} else {
						// Quote at end of string, assume literal "
						$s .= $code;
					}
					break;
				default:
					// Plain text - convert to language variant
					$s .= $langConv->translate( $format[$p], $variant );
			}
		}

		return $s;
	}
}
