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

use MediaWiki\Skins\Vector\Components\VectorComponentPinnableHeader;
use MediaWikiUnitTestCase;
use Message;
use MessageLocalizer;

/**
 * @group Vector
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Vector\Components\VectorComponentPinnableHeader
 */
class VectorComponentPinnableHeaderTest extends MediaWikiUnitTestCase {

	/**
	 * This method provides different sets of parameters for tests, simulating different scenarios.
	 * @return array[]
	 */
	public function provideTestCases(): array {
		return [
			// Test case with the header element intended to be movable.
			'Pinnable Header With Moving Element' => [
				// The header should start in a "pinned" state.
				'pinned' => true,
				// ID of the pinnable header element.
				'id' => 'vector-example',
				// Feature name for persistent state tracking.
				'featureName' => 'example-pinned',
				// Indicates the header is expected to move in the DOM.
				'moveElement' => true,
				// The type of label tag to be used.
				'labelTagName' => 'div'
			],
			// Test case with the header element fixed and not intended to be moved.
			'Pinnable Header Without Moving Element' => [
				// The header should start in an "unpinned" state.
				'pinned' => false,
				// ID of the pinnable header element.
				'id' => 'vector-another-example',
				// Feature name for tracking state.
				'featureName' => 'another-example-pinned',
				// Indicates the header will not move in the DOM.
				'moveElement' => false,
				// The type of label tag to be used, in this case, an h2.
				'labelTagName' => 'h2'
			],
		];
	}

	/**
	 * Tests that the getTemplateData method returns the correct data.
	 * Uses data provided by provideTestCases to run the same test with different configurations.
	 * @covers ::getTemplateData
	 * @dataProvider provideTestCases
	 */
	public function testGetTemplateData( $pinned, $id, $featureName, $moveElement, $labelTagName ) {
		// Mocking the MessageLocalizer to provide predictable responses for given message keys.
		$localizer = $this->createMock( MessageLocalizer::class );
		$localizer->method( 'msg' )->willReturnCallback( function ( $key ) {
			return $this->createConfiguredMock( Message::class, [
				// Simulated localization output.
				'__toString' => $key . '-mocked-label',
			] );
		} );

		// Instantiating the component with the provided test parameters.
		$pinnableHeader = new VectorComponentPinnableHeader(
			$localizer,
			$pinned,
			$id,
			$featureName,
			$moveElement,
			$labelTagName
		);

		// Acquiring the template data from the component.
		$templateData = $pinnableHeader->getTemplateData();

		// Assertions to verify each piece of expected template data.
		$this->assertEquals( $pinned, $templateData['is-pinned'] );
		$this->assertStringEndsWith( '-mocked-label', $templateData['label'] );
		$this->assertEquals( $labelTagName, $templateData['label-tag-name'] );
		$this->assertStringEndsWith( '-mocked-label', $templateData['pin-label'] );
		$this->assertStringEndsWith( '-mocked-label', $templateData['unpin-label'] );
		$this->assertEquals( $id, $templateData['data-pinnable-element-id'] );
		$this->assertEquals( $featureName, $templateData['data-feature-name'] );

		// Additional checks for elements' IDs when the element can move.
		if ( $moveElement ) {
			$this->assertEquals( $id . '-unpinned-container', $templateData['data-unpinned-container-id'] );
			$this->assertEquals( $id . '-pinned-container', $templateData['data-pinned-container-id'] );
		} else {
			$this->assertArrayNotHasKey( 'data-unpinned-container-id', $templateData );
			$this->assertArrayNotHasKey( 'data-pinned-container-id', $templateData );
		}
	}
}
