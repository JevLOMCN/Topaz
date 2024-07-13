<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\NodeData;

use Wikimedia\Bcp47Code\Bcp47Code;

class I18nInfo {
	public const USER_LANG = "x-user";
	public const PAGE_LANG = "x-page";

	/**
	 * Value for the "lang" parameter. Can be one of USER_LANG or PAGE_LANG, or a fixed language
	 * code (discouraged when USER_LANG or PAGE_LANG could be used instead).
	 * @var string
	 */
	public $lang;

	/**
	 * Key of the message in localization files
	 * @var string
	 */
	public $key;

	/**
	 * Ordered list of parameters for the localized message
	 * @var ?array
	 */
	public $params;

	public function __construct( string $lang, string $key, ?array $params = null ) {
		$this->lang = $lang;
		$this->key = $key;
		$this->params = $params;
	}

	/**
	 * Creates internationalization information for a string or attribute value in the user
	 * interface language.
	 * @param string $key
	 * @param array|null $params
	 * @return I18nInfo
	 */
	public static function createInterfaceI18n( string $key, ?array $params ): I18nInfo {
		return new I18nInfo( self::USER_LANG, $key, $params );
	}

	/**
	 * Creates internationalization information for a string or attribute value in the page
	 * content language.
	 * @param string $key
	 * @param array|null $params
	 * @return I18nInfo
	 */
	public static function createPageContentI18n( string $key, ?array $params ): I18nInfo {
		return new I18nInfo( self::PAGE_LANG, $key, $params );
	}

	/**
	 * Creates internationalization information for a string or attribute value in an arbitrary
	 * language.
	 * The use of this method is discouraged; use ::createPageContentI18n(...) and
	 * ::createInterfaceI18n(...) where possible rather than, respectively,
	 * ::createLangI18n($wgContLang, ...) and ::createLangI18n($wgLang, ...).
	 * @param Bcp47Code $lang
	 * @param string $key
	 * @param array|null $params
	 * @return I18nInfo
	 */
	public static function createLangI18n( Bcp47Code $lang, string $key, ?array $params ): I18nInfo {
		return new I18nInfo( $lang->toBcp47Code(), $key, $params );
	}
}
