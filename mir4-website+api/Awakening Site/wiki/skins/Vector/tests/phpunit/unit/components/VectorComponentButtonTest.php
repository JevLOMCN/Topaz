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

use MediaWiki\Skins\Vector\Components\VectorComponentButton;
use MediaWikiUnitTestCase;

/**
 * @group Vector
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Vector\Components\VectorComponentButton
 */
class VectorComponentButtonTest extends MediaWikiUnitTestCase {

	/**
	 * Provides various configurations of VectorComponentButton to test different scenarios.
	 * Each case includes different combinations of the button's properties.
	 *
	 * @return array[] An array of test cases with parameters and expected values.
	 */
	public function provideButtonData(): array {
		return [
			'Basic Button' => [
				// The visible text on the button.
				'label' => 'Click Me',
				// CSS classes expected without additional properties.
				'expectedClasses' => 'cdx-button',
				// Default button weight.
				'weight' => 'normal',
				// Indicates that the button is not icon-only.
				'iconOnly' => false,
				// No link for a basic button.
				'href' => null,
			],
			'Button With Primary Weight' => [
				// The visible text indicating a primary action.
				'label' => 'Primary Action',
				// Additional classes are expected due to the primary weight.
				'expectedClasses' =>
					'cdx-button cdx-button--fake-button cdx-button--fake-button--enabled cdx-button--weight-primary',
				// Indicates primary visual importance.
				'weight' => 'primary',
				// Still not an icon-only button.
				'iconOnly' => false,
				// Providing an href activates additional styles.
				'href' => '/mock-link',
			],
			'Icon Only Button' => [
				// No visible text for an icon-only button.
				'label' => '',
				// CSS classes specifically for icon-only.
				'expectedClasses' => 'cdx-button cdx-button--icon-only',
				// Default weight even for icon-only buttons.
				'weight' => 'normal',
				// This button is icon-only.
				'iconOnly' => true,
				// No link for this icon-only button.
				'href' => null,
			],
		];
	}

	/**
	 * Tests CSS class generation logic within VectorComponentButton.
	 * This method verifies that the class string is generated correctly based on the button's properties.
	 *
	 * @covers ::getClasses
	 */
	public function testGetClasses() {
		$basicButton = new VectorComponentButton( 'Label' );
		$templateData = $basicButton->getTemplateData();
		$this->assertStringContainsString( 'cdx-button', $templateData['class'],
			'Basic button should have cdx-button class.' );

		$primaryButton = new VectorComponentButton( 'Label', null, null, null, [], 'primary' );
		$templateData = $primaryButton->getTemplateData();
		$this->assertStringContainsString( 'cdx-button--weight-primary', $templateData['class'],
			'Primary button should have primary weight class.' );

		$iconOnlyButton = new VectorComponentButton(
			'Label', null, null, null, [], 'normal', 'default', true );
		$templateData = $iconOnlyButton->getTemplateData();
		$this->assertStringContainsString( 'cdx-button--icon-only', $templateData['class'],
			'Icon-only button should have icon-only class.' );

		$destructiveButton = new VectorComponentButton( 'Label', null, null, null, [],
			'normal', 'destructive' );
		$templateData = $destructiveButton->getTemplateData();
		$this->assertStringContainsString( 'cdx-button--action-destructive', $templateData['class'],
			'Destructive button should have destructive action class.' );

		$progressiveButton = new VectorComponentButton( 'Label', null, null, null, [],
			'normal', 'progressive' );
		$templateData = $progressiveButton->getTemplateData();
		$this->assertStringContainsString( 'cdx-button--action-progressive', $templateData['class'],
			'Progressive button should have progressive action class.' );

		$quietButton = new VectorComponentButton( 'Label', null, null, null, [], 'quiet' );
		$templateData = $quietButton->getTemplateData();
		$this->assertStringContainsString( 'cdx-button--weight-quiet', $templateData['class'],
			'Quiet button should have quiet weight class.' );
	}

	/**
	 * Tests the `getTemplateData` method of VectorComponentButton component.
	 * Each data set provided by `provideButtonData` is passed here to verify the component's output.
	 *
	 * @covers ::__construct
	 * @dataProvider provideButtonData
	 */
	public function testGetTemplateData(
		string $label,
		string $expectedClasses,
		string $weight,
		bool $iconOnly,
		?string $href
	) {
		// Instantiate the component with the provided configuration.
		$button = new VectorComponentButton(
			$label,
			'icon-sample',
			'btn-id',
			'additional-class',
			// Custom data attribute as an example.
			[ 'data-test' => 'true' ],
			$weight,
			// Default action type.
			'default',
			$iconOnly,
			$href
		);

		// Acquire the generated template data from the component.
		$templateData = $button->getTemplateData();

		// Assert each aspect of the template data matches expectations.
		$this->assertEquals( $label, $templateData['label'] );
		$this->assertEquals( 'icon-sample', $templateData['icon'] );
		$this->assertEquals( 'btn-id', $templateData['id'] );
		// Ensures the class string contains all expected CSS classes.
		$this->assertStringContainsString( $expectedClasses, $templateData['class'] );
		$this->assertEquals( $href, $templateData['href'] );
		// Verifies custom attributes are included appropriately.
		$this->assertContains( [ 'key' => 'data-test', 'value' => 'true' ], $templateData['array-attributes'] );
	}
}
