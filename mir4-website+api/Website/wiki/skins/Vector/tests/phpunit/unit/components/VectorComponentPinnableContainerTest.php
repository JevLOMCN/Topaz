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

use MediaWiki\Skins\Vector\Components\VectorComponentPinnableContainer;
use MediaWikiUnitTestCase;

/**
 * @group Vector
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Vector\Components\VectorComponentPinnableContainer
 */
class VectorComponentPinnableContainerTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::getTemplateData
	 */
	public function testGetTemplateData() {
		// Test initialization with ID and isPinned set to true
		$mockIdTrue = 'pinned-container';
		$pinnableContainerTrue = new VectorComponentPinnableContainer( $mockIdTrue, true );
		$expectedTemplateDataTrue = [
			'id' => $mockIdTrue,
			'is-pinned' => true
		];
		// Assert that the actual template data matches the expected array for pinned=true
		$this->assertSame( $expectedTemplateDataTrue, $pinnableContainerTrue->getTemplateData(),
			'Template data for a pinned container should correctly include the ID and is-pinned status.' );

		// Test initialization with ID and isPinned set to false
		$mockIdFalse = 'unpinned-container';
		$pinnableContainerFalse = new VectorComponentPinnableContainer( $mockIdFalse, false );
		$expectedTemplateDataFalse = [
			'id' => $mockIdFalse,
			'is-pinned' => false
		];
		// Assert that the actual template data matches the expected array for pinned=false
		$this->assertSame( $expectedTemplateDataFalse, $pinnableContainerFalse->getTemplateData(),
			'Template data for an unpinned container should accurately reflect a different ID and is-pinned status.' );
	}
}
