<?php
/**
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
 *
 * @file
 */
namespace MediaWiki\Minerva;

use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;

/**
 * Helper class to encapsulate logic responsible for checking languages and variants for given title
 * @package MediaWiki\Minerva
 */
class LanguagesHelper {

	private LanguageConverterFactory $languageConverterFactory;

	/**
	 * @param LanguageConverterFactory $languageConverterFactory
	 */
	public function __construct(
		LanguageConverterFactory $languageConverterFactory
	) {
		$this->languageConverterFactory = $languageConverterFactory;
	}

	/**
	 * Whether the Title is also available in other languages or variants
	 * @param OutputPage $out Output page to fetch language links
	 * @param Title $title
	 * @return bool
	 */
	public function doesTitleHasLanguagesOrVariants(
		OutputPage $out,
		Title $title
	) {
		if ( $out->getLanguageLinks() !== [] ) {
			return true;
		}
		$langConv = $this->languageConverterFactory->getLanguageConverter( $title->getPageLanguage() );
		return $langConv->hasVariants();
	}
}
