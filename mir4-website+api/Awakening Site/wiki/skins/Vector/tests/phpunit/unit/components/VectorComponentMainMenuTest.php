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
use MediaWiki\Skins\Vector\Components\VectorComponentMainMenu;
use MediaWiki\Skins\Vector\Constants;
use MediaWiki\Skins\Vector\FeatureManagement\FeatureManager;
use MediaWiki\User\UserIdentity;
use MediaWikiUnitTestCase;
use MessageLocalizer;
use Skin;

/**
 * @group Vector
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Vector\Components\VectorComponentMainMenu
 */
class VectorComponentMainMenuTest extends MediaWikiUnitTestCase {

	/**
	 * This test checks if the VectorComponentMainMenu class can be instantiated
	 * @covers ::__construct
	 */
	public function testConstruct() {
		// Mock the sidebar data, number of languages, and language data
		$sidebarData = [];
		$languageData = [];

		// Mock the MessageLocalizer, UserIdentity, FeatureManager, and Skin classes
		$localizerMock = $this->createMock( MessageLocalizer::class );
		$userMock = $this->createMock( UserIdentity::class );
		$featureManagerMock = $this->createMock( FeatureManager::class );
		$skinMock = $this->createMock( Skin::class );

		// Create a new VectorComponentMainMenu object
		$mainMenu = new VectorComponentMainMenu(
			$sidebarData,
			$languageData,
			$localizerMock,
			$userMock,
			$featureManagerMock,
			$skinMock
		);

		// Assert that the object is an instance of VectorComponent
		$this->assertInstanceOf( VectorComponent::class, $mainMenu );
	}

	/**
	 * @return array[]
	 */
	public function provideMainMenuScenarios(): array {
		return [
			'Main Menu Pinned' => [
				'sidebarData' => [
					'data-portlets-first' => [],
					'array-portlets-rest' => [],
				],
				'languageData' => [],
				'isPinned' => true,
			],
			'Main Menu Not Pinned' => [
				'sidebarData' => [
					'data-portlets-first' => [],
					'array-portlets-rest' => [],
				],
				'languageData' => [],
				'isPinned' => false,
			],
		];
	}

	/**
	 * @covers ::getTemplateData
	 * @dataProvider provideMainMenuScenarios
	 */
	public function testGetTemplateData( array $sidebarData, array $languageData, bool $isPinned ) {
		// Mock the MessageLocalizer, UserIdentity, FeatureManager, and Skin classes
		$localizerMock = $this->createMock( MessageLocalizer::class );
		$userMock = $this->createMock( UserIdentity::class );
		$featureManagerMock = $this->createMock( FeatureManager::class );

		// Mock the isFeatureEnabled method
		$featureManagerMock->expects( $this->once() )
			->method( 'isFeatureEnabled' )
			->with( Constants::FEATURE_MAIN_MENU_PINNED )
			->willReturn( $isPinned );

		// Mock the Skin class
		$skinMock = $this->createMock( Skin::class );

		// Create a new VectorComponentMainMenu object
		$mainMenu = new VectorComponentMainMenu(
			$sidebarData,
			$languageData,
			$localizerMock,
			$userMock,
			$featureManagerMock,
			$skinMock
		);

		// Call the getTemplateData method
		$templateData = $mainMenu->getTemplateData();

		// Assert main menu id and pin status
		$this->assertSame( 'vector-main-menu', $templateData['id'] );
		$this->assertSame( $isPinned, $templateData['is-pinned'] );

		// Assert the structure and types of expected keys
		$this->assertIsArray( $templateData['data-portlets-first'] );
		$this->assertIsArray( $templateData['array-portlets-rest'] );
		$this->assertNull( $templateData['data-main-menu-action'] );

		// Assert data-pinnable-header
		$this->assertIsArray( $templateData['data-pinnable-header'] );
		$this->assertIsArray( $templateData['data-languages'] );

		// Assert the structure and types of expected keys
		$this->assertArrayHasKey( 'data-portlets-first', $templateData );
		$this->assertArrayHasKey( 'array-portlets-rest', $templateData );
		$this->assertArrayHasKey( 'data-main-menu-action', $templateData );
		$this->assertArrayHasKey( 'data-pinnable-header', $templateData );
		$this->assertArrayHasKey( 'data-languages', $templateData );
	}
}
