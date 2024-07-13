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

use MediaWiki\Skins\Vector\Components\VectorComponentLink;
use MediaWikiUnitTestCase;
use Message;
use MessageLocalizer;

/**
 * @group Vector
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Vector\Components\VectorComponentLink
 */
class VectorComponentLinkTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::getTemplateData
	 */
	public function testGetTemplateData() {
		$href = '/mock-link';
		$text = 'Mock Text';
		$icon = 'mock-icon';
		$accessKeyHint = 'sample-accesskey';

		$localizer = $this->createMock( MessageLocalizer::class );
		// Adjusting mock to prevent calling the service container.
		$localizer->method( 'msg' )
			->willReturnCallback( function ( $key ) use ( $accessKeyHint ) {
				// Directly create Message object without accessing real message texts
				// to avoid 'Premature access to service container' error.
				return $this->createConfiguredMock( Message::class, [
					'exists' => true,
					'text' => $key === $accessKeyHint . '-label' ? 'Mock aria label' : $key,
					'__toString' => 'Mock aria label',
				] );
			} );

		// Create the component
		$linkComponent = new VectorComponentLink( $href, $text, $icon, $localizer, $accessKeyHint );
		$actual = $linkComponent->getTemplateData();

		// Assert the expected values
		$this->assertEquals( $icon, $actual['icon'] );
		$this->assertEquals( $text, $actual['text'] );
		$this->assertEquals( $href, $actual['href'] );

		// New assertions for HTML attributes
		$expectedTitle = "tooltip-sample-accesskeyword-separatorbrackets";
		$expectedAriaLabel = "Mock aria label";
		$attributesString = $actual['html-attributes'];

		// Assert that the expected attributes are present in the string
		$this->assertStringContainsString( 'title="' . $expectedTitle . '"', $attributesString );
		$this->assertStringContainsString( 'aria-label="' . $expectedAriaLabel . '"', $attributesString );
	}
}
