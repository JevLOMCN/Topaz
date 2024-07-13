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

namespace MediaWiki\Linter;

class HtmlTags {
	/**
	 * @var array|null
	 */
	private static $allowedHtmlTags = null;

	/**
	 * @param SpecialLintErrors|LintErrorsPager $parent
	 */
	public function __construct( $parent ) {
		// reuse the allowed html tags array once constructed
		if ( self::$allowedHtmlTags === null ) {
			$tagOptionAll = $parent->msg( 'linter-form-tag-option-all' )->escaped();
			self::$allowedHtmlTags = $this->createAllowedHTMLTags();

			// prepend the translatable word for 'all' to the associative array
			self::$allowedHtmlTags = array_merge( [ $tagOptionAll => 'all' ], self::$allowedHtmlTags );
		}
	}

	/**
	 * Create an associative array out of all valid and deprecated HTML tags
	 * @return array
	 */
	private function createAllowedHTMLTags(): array {
		$allowedHtmlTags = [
			'a',
			'abbr',
			'b', 'bdi', 'bdo', 'big', 'blockquote', 'br',
			'caption', 'center', 'cite', 'code',
			'data', 'dd', 'del', 'dfn', 'div', 'dl', 'dt',
			'em',
			'font',
			'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr',
			'i', 'ins',
			'kbd',
			'li',
			'mark',
			'ol',
			'p', 'pre',
			'q',
			'rb', 'rp', 'rt', 'rtc', 'ruby',
			's', 'samp', 'small', 'span', 'strike', 'strong', 'sub', 'sup',
			'table', 'td', 'th', 'time', 'tr', 'tt',
			'u', 'ul',
			'var',
			'wbr',
		];
		// create an associative array where allowed tags are set as both keys and values used
		// to create UI drop down list in SpecialLintErrors.php and for user search URL string
		// tag validation for a SQL query in the LinterErrorsPager.php code.
		return array_combine( $allowedHtmlTags, $allowedHtmlTags );
	}

	/**
	 * @return array
	 */
	public function getAllowedHTMLTags(): array {
		return self::$allowedHtmlTags;
	}

	/**
	 * @param string $tag
	 * @return bool
	 */
	public function checkAllowedHTMLTags( string $tag ): bool {
		return in_array( $tag, self::$allowedHtmlTags, true );
	}

}
