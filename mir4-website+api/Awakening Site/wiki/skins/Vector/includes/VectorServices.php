<?php

namespace MediaWiki\Skins\Vector;

use MediaWiki\Skins\Vector\Services\LanguageService;

/**
 * A service locator for services specific to Vector.
 *
 * @package Vector
 * @internal
 */
final class VectorServices {

	/**
	 * Gets the language service.
	 *
	 * @return LanguageService
	 */
	public static function getLanguageService(): LanguageService {
		return new LanguageService();
	}
}
