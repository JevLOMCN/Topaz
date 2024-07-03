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

use MediaWiki\Skins\Vector\Components\VectorComponentDropdown;
use MediaWikiUnitTestCase;

/**
 * @group Vector
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Vector\Components\VectorComponentDropdown
 */
class VectorComponentDropdownTest extends MediaWikiUnitTestCase {

	/**
	 * @return array[]
	 */
	public function provideDropdownData(): array {
		return [
			'Dropdown' => [
				'id' => 'mock-dropdown',
				'label' => 'Mock Dropdown',
				'class' => 'some-class',
				'icon' => null,
				'tooltip' => 'A tooltip for the dropdown',
				'expectedClasses' => 'some-class',
				'expectedIconButtonClasses' => '',
			],
			'Dropdown with icon' => [
				'id' => 'mock-icon-dropdown',
				'label' => 'Mock Icon Dropdown',
				'class' => 'some-icon-class',
				'icon' => 'icon-some',
				'tooltip' => 'A tooltip for the icon dropdown',
				'expectedClasses' => 'some-icon-class',
				'expectedIconButtonClasses' => 'cdx-button--icon-only',
			],
		];
	}

	/**
	 * @covers ::getTemplateData
	 * @dataProvider provideDropdownData
	 */
	public function testGetTemplateData( string $id, string $label, string $class, $icon, string $tooltip,
		string $expectedClasses, string $expectedIconButtonClasses ) {
		// Create a new VectorComponentDropdown object
		$dropdown = new VectorComponentDropdown( $id, $label, $class, $icon, $tooltip );
		// Call the getTemplateData method
		$templateData = $dropdown->getTemplateData();

		// Verifying that the template data is constructed as expected
		$this->assertEquals( $id, $templateData['id'] );
		$this->assertEquals( $label, $templateData['label'] );
		$this->assertEquals( $expectedClasses, $templateData['class'] );
		$this->assertEquals( $icon, $templateData['icon'] );
		$this->assertEquals( $tooltip, $templateData['html-tooltip'] );
		// Verifying that the label-class is constructed as expected
		$this->assertStringContainsString( $expectedIconButtonClasses, $templateData['label-class'] );
	}
}
