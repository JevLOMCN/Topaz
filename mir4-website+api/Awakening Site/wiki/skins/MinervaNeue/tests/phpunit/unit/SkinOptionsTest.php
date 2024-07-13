<?php

namespace MediaWiki\Minerva;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Minerva\Skins\SkinUserPageHelper;
use MediaWikiUnitTestCase;
use OutOfBoundsException;

/**
 * @package Tests\MediaWiki\Minerva
 * @group MinervaNeue
 * @coversDefaultClass \MediaWiki\Minerva\SkinOptions
 */
class SkinOptionsTest extends MediaWikiUnitTestCase {

	private function newSkinOptions() {
		return new SkinOptions(
			$this->createMock( HookContainer::class ),
			$this->createMock( SkinUserPageHelper::class )
		);
	}

	/**
	 * @covers ::get
	 * @covers ::getAll
	 * @covers ::setMultiple
	 */
	public function testSettersAndGetters() {
		$options = $this->newSkinOptions();
		$defaultValue = $options->get( SkinOptions::BETA_MODE );
		$options->setMultiple( [ SkinOptions::BETA_MODE => !$defaultValue ] );

		$allOptions = $options->getAll();

		$this->assertEquals( !$defaultValue, $options->get( SkinOptions::BETA_MODE ) );
		$this->assertArrayHasKey( SkinOptions::BETA_MODE, $allOptions );
		$this->assertEquals( !$defaultValue, $allOptions[ SkinOptions::BETA_MODE ] );
	}

	/**
	 * @covers ::hasSkinOptions
	 */
	public function testHasSkinOptions() {
		$options = $this->newSkinOptions();
		$this->assertTrue( $options->hasSkinOptions() );
		$options->setMultiple( [
			SkinOptions::SHOW_DONATE => false,
			SkinOptions::TALK_AT_TOP => false,
			SkinOptions::HISTORY_IN_PAGE_ACTIONS => false,
			SkinOptions::TOOLBAR_SUBMENU => false,
			SkinOptions::MAIN_MENU_EXPANDED => false,
			SkinOptions::PERSONAL_MENU => false,
			SkinOptions::TABS_ON_SPECIALS => false,
		] );
		$this->assertFalse( $options->hasSkinOptions() );
	}

	/**
	 * @covers ::get
	 */
	public function testGettingUnknownKeyShouldThrowException() {
		$options = $this->newSkinOptions();
		$this->expectException( OutOfBoundsException::class );
		$options->get( 'non_existing_key' );
	}

	/**
	 * @covers ::get
	 */
	public function testSettingUnknownKeyShouldThrowException() {
		$options = $this->newSkinOptions();
		$this->expectException( OutOfBoundsException::class );
		$options->setMultiple( [
			'non_existing_key' => 1
		] );
	}
}
