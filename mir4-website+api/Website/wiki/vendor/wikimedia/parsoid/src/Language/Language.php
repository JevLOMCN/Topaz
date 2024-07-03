<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

/**
 * Base class for Language objects.
 */
class Language {

	/** @var LanguageConverter|null */
	private $converter;

	public function getConverter(): ?LanguageConverter {
		return $this->converter;
	}

	public function setConverter( LanguageConverter $converter ): void {
		$this->converter = $converter;
	}

	/**
	 * Returns true if a language code string is of a valid form, whether or not it exists.
	 * This includes codes which are used solely for customisation via the MediaWiki namespace.
	 * @param string $code a MediaWiki-internal code
	 * @return bool
	 */
	public static function isValidInternalCode( string $code ): bool {
		static $validityCache = [];
		if ( !isset( $validityCache[$code] ) ) {
			$validityCache[$code] = strcspn( $code, ":/\\\000&<>'\"" ) === strlen( $code ) &&
				// XXX Core's version also checks against
				// !preg_match( MediaWikiTitleCodec::getTitleInvalidRegex(), $code ) &&
				strlen( $code ) <= 128;
		}
		return $validityCache[$code];
	}

	/**
	 * Get an array of language names, indexed by code.
	 * @param string $inLanguage Code of language in which to return the names.
	 *   Use null for autonyms (native names)
	 * @param string $include One of:
	 *   * `all` all available languages
	 *   * `mw` only if the language is defined in MediaWiki or `wgExtraLanguageNames` (default)
	 *   * `mwfile` only if the language is in `mw` *and* has a message file
	 * @return array
	 * @deprecated Appears to be unused
	 */
	public function fetchLanguageNames( string $inLanguage, string $include ): array {
		return [];
	}

}
