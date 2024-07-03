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

use MediaWiki\Skins\Vector\Components\VectorComponent;
use MediaWiki\Skins\Vector\Components\VectorComponentMainMenuAction;
use MediaWikiUnitTestCase;
use Skin;

/**
 * @group Vector
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Vector\Components\VectorComponentMainMenuAction
 */
class VectorComponentMainMenuActionTest extends MediaWikiUnitTestCase {

	/**
	 * This test checks if the VectorComponentMainMenuAction class can be instantiated
	 * @covers ::__construct
	 */
	public function testConstruct() {
		// Mock the Skin, htmlData, headingOptions, and classes
		$skinMock = $this->createMock( Skin::class );
		$htmlData = [];
		$headingOptions = [];
		$classes = '';

		// Create a new VectorComponentMainMenuAction object
		$mainMenuAction = new VectorComponentMainMenuAction(
			'actionName',
			$skinMock,
			$htmlData,
			$headingOptions,
			$classes
		);

		// Check if the object is an instance of VectorComponent
		$this->assertInstanceOf( VectorComponent::class, $mainMenuAction );
	}

	/**
	 * This test checks if the makeMainMenuActionData method returns the correct data
	 * @covers ::getTemplateData
	 */
	public function testMakeMainMenuActionData() {
		// Mock the Skin
		$skinMock = $this->createMock( Skin::class );

		// Create a new VectorComponentMainMenuAction object
		$mainMenuAction = new VectorComponentMainMenuAction(
			'action-example',
			$skinMock,
			[ 'html-content' => '<a href="/example">Some Link</a>' ],
			[ 'heading' => 'Some Heading' ],
			'some-class'
		);

		// Call the getTemplateData method
		$templateData = $mainMenuAction->getTemplateData();

		// Verifying that the template data is constructed as expected
		$this->assertEquals( 'Some Heading', $templateData['heading'] );
		$this->assertStringContainsString( 'Some Link', $templateData['html-content'] );
		$this->assertEquals( 'some-class', $templateData['class'] );
	}
}
