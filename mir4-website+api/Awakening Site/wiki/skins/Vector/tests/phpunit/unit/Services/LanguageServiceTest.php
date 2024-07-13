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
 * $since 1.42
 */

namespace MediaWiki\Skins\Vector\Tests\Unit\Services;

use MediaWiki\Skins\Vector\Services\LanguageService;
use MediaWikiUnitTestCase;

/**
 * @group Vector
 * @group Services
 * @covers  \MediaWiki\Skins\Vector\Services\LanguageService
 */
class LanguageServiceTest extends MediaWikiUnitTestCase {

	public function testConstructor() {
		$languageService = new LanguageService();
		$this->assertInstanceOf( LanguageService::class, $languageService );
	}

	public function provideSplittableLanguages(): array {
		return [
			[ 'en', true ],
			[ 'de', true ],
			[ 'fr', true ],
			[ 'ja', true ],
			[ 'ru', true ],
			[ 'zh', true ],
			[ 'ar', false ],
			[ 'fa', false ],
		];
	}

	/**
	 * @dataProvider provideSplittableLanguages
	 */
	public function testCanWordsBeSplitSafely( string $code, bool $expected ) {
		$languageService = new LanguageService();
		$result = $languageService->canWordsBeSplitSafely( $code );
		$this->assertSame( $expected, $result );
	}
}
