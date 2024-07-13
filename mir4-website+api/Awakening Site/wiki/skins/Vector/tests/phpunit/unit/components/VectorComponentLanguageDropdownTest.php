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

use MediaWiki\Skins\Vector\Components\VectorComponentLanguageDropdown;
use MediaWiki\Title\Title;
use MediaWikiUnitTestCase;

/**
 * @group Vector
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Vector\Components\VectorComponentLanguageDropdown
 */
class VectorComponentLanguageDropdownTest extends MediaWikiUnitTestCase {

	/**
	 * @return array[]
	 */
	public function provideLanguageDropdownData(): array {
		return [
			'Subject page with languages' => [
				'label' => 'Languages',
				'ariaLabel' => 'Choose language',
				'class' => 'some-class',
				'numLanguages' => 5,
				'itemHTML' => '<li>Language Mock</li>',
				'titleExists' => true,
				'expectedIcon' => 'language-progressive',
				'isSubjectPage' => true,
			],
			'Talk page without languages' => [
				'label' => 'Languages',
				'ariaLabel' => 'Choose language',
				'class' => 'some-class',
				'numLanguages' => 0,
				'itemHTML' => '',
				'titleExists' => false,
				'expectedIcon' => 'language',
				'isSubjectPage' => false,
			],
			'Subject page without languages' => [
				'label' => 'Languages',
				'ariaLabel' => 'Choose language',
				'class' => 'some-class',
				'numLanguages' => 0,
				'itemHTML' => '',
				'titleExists' => true,
				'expectedIcon' => 'language-progressive',
				'isSubjectPage' => true,
			],
			'Talk page with languages' => [
				'label' => 'Languages',
				'ariaLabel' => 'Choose language',
				'class' => 'some-class',
				'numLanguages' => 5,
				'itemHTML' => '<li>Language Mock</li>',
				'titleExists' => false,
				'expectedIcon' => 'language',
				'isSubjectPage' => false,
			],
		];
	}

	/**
	 * @covers ::getTemplateData
	 * @dataProvider provideLanguageDropdownData
	 */
	public function testGetTemplateData( $label, $ariaLabel, $class, $numLanguages, $itemHTML, $titleExists,
		$expectedIcon, $isSubjectPage ) {
		// Mock Title
		$titleMock = $this->createMock( Title::class );
		// Mock Title methods
		$titleMock->method( 'exists' )->willReturn( $titleExists );
		$titleMock->method( 'isTalkPage' )->willReturn( !$titleExists );

		// Create a new VectorComponentLanguageDropdown object
		$languageDropdown = new VectorComponentLanguageDropdown(
			$label, $ariaLabel, $class, $numLanguages, $itemHTML, '', '', $titleMock
		);

		// Call the getTemplateData method
		$templateData = $languageDropdown->getTemplateData();

		// Verifying that the template data is constructed as expected
		$this->assertEquals( $label, $templateData['label'] );
		$this->assertEquals( $ariaLabel, $templateData['aria-label'] );
		$this->assertEquals( $expectedIcon, $templateData['icon'] );
		$this->assertStringContainsString( 'cdx-button--fake-button', $templateData['label-class'] );
		$this->assertEquals( $itemHTML, $templateData['html-items'] );
		$this->assertSame( !$titleExists, $templateData['is-language-selector-empty'] );

		// Verifying that the template data is constructed as expected
		if ( !$isSubjectPage ) {
			$this->assertStringContainsString( 'cdx-button--fake-button', $templateData['label-class'] );
			$this->assertStringContainsString( 'cdx-button--icon-only', $templateData['label-class'] );
			$this->assertStringContainsString( 'mw-portlet-lang-heading-empty', $templateData['label-class'] );
			$this->assertStringContainsString( 'mw-interlanguage-selector-empty', $templateData['checkbox-class'] );
			$this->assertStringContainsString( 'mw-portlet-lang-icon-only', $templateData['class'] );

		} else {
			$this->assertStringContainsString( 'cdx-button--fake-button', $templateData['label-class'] );
			$this->assertStringNotContainsString( 'cdx-button--icon-only', $templateData['label-class'] );
			$this->assertStringNotContainsString( 'mw-portlet-lang-heading-empty', $templateData['label-class'] );
			$this->assertStringNotContainsString( 'mw-interlanguage-selector-empty', $templateData['checkbox-class'] );
			$this->assertStringNotContainsString( 'mw-portlet-lang-icon-only', $templateData['class'] );
		}
	}
}
