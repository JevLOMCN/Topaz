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
 * @since 1.42
 */

namespace MediaWiki\Skins\Vector\Tests\Unit\Components;

use MediaWiki\Skins\Vector\Components\VectorComponentPinnableElement;
use MediaWikiUnitTestCase;

/**
 * @group Vector
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Vector\Components\VectorComponentPinnableElement
 */
class VectorComponentPinnableElementTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::getTemplateData
	 */
	public function testGetTemplateData() {
		// Test with a mock ID
		$mockId = 'mock-element';
		$pinnableElement = new VectorComponentPinnableElement( $mockId );

		// Expected template data
		$expectedTemplateData = [
			'id' => $mockId,
		];

		// Fetching template data for the element
		$actualTemplateData = $pinnableElement->getTemplateData();

		// Assert that the actual template data matches the expected array
		$this->assertSame( $expectedTemplateData, $actualTemplateData,
			'Template data should correctly include the ID.' );

		// Additional test case to verify behavior with different ID
		$anotherMockId = 'another-mock-element';
		$anotherPinnableElement = new VectorComponentPinnableElement( $anotherMockId );

		// Fetching template data for the new element
		$anotherActualTemplateData = $anotherPinnableElement->getTemplateData();

		// Ensuring the new element's template data is as expected
		$this->assertSame( [ 'id' => $anotherMockId ], $anotherActualTemplateData,
			'Template data should accurately reflect a different ID.' );
	}
}
