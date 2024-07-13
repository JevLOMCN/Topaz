<?php

namespace MediaWiki\Minerva\Menu;

use DomainException;
use MediaWiki\Minerva\Menu\Entries\IMenuEntry;
use MediaWiki\Minerva\Menu\Entries\SingleMenuEntry;
use MediaWikiIntegrationTestCase;

/**
 * @group MinervaNeue
 * @coversDefaultClass \MediaWiki\Minerva\Menu\Group
 */
class GroupTest extends MediaWikiIntegrationTestCase {
	/** @var string[] */
	private $homeComponent = [
		'text' => 'Home',
		'href' => '/Main_page',
		'class' => '',
		'data-event-name' => 'menu.home',
		'icon' => 'home'
	];

	/** @var string[] */
	private $nearbyComponent = [
		'text' => 'Nearby',
		'href' => '/wiki/Special:Nearby',
		'class' => '',
		'icon' => 'nearby'
	];

	/**
	 * @covers ::getEntries
	 * @covers ::hasEntries
	 */
	public function testItShouldntHaveEntriesByDefault() {
		$menu = new Group( 'p-test' );

		$this->assertSame( [], $menu->getEntries() );
		$this->assertFalse( $menu->hasEntries() );
	}

	/**
	 * @covers ::insertEntry
	 * @covers ::search
	 * @covers ::getEntries
	 * @covers ::hasEntries
	 */
	public function testInsertingAnEntry() {
		$menu = new Group( 'p-test' );
		$entry = SingleMenuEntry::create(
			'home',
			$this->homeComponent['text'],
			$this->homeComponent['href'],
			$this->homeComponent['class'],
			$this->homeComponent['icon'],
			true
		);
		$menu->insertEntry( $entry );

		$expectedEntries = [
			[
				'name' => 'home',
				'components' => [
					[
						'tag-name' => 'a',
						'label' => $this->homeComponent['text'],
						'array-attributes' => [
							[
								'key' => 'href',
								'value' => $this->homeComponent['href'],
							],
							[
								'key' => 'data-event-name',
								'value' => 'menu.home'
							],
							[
								'key' => 'data-mw',
								'value' => 'interface'
							],
						],
						'classes' => 'menu__item--home',
						'data-icon' => [
							'icon' => 'home',
						],
					]
				 ],
			],
		];

		$this->assertEquals( $expectedEntries, $menu->getEntries() );
		$this->assertTrue( $menu->hasEntries() );
	}

	/**
	 * @covers ::insertEntry
	 */
	public function testInsertingAnEntryWithAnExistingName() {
		$menu = new Group( 'p-test' );
		$entryHome = SingleMenuEntry::create(
			'home',
			$this->homeComponent['text'],
			$this->homeComponent['href'],
			$this->homeComponent['class']
		);
		$menu->insertEntry( $entryHome );
		$this->expectException( DomainException::class );
		$this->expectExceptionMessage( 'The "home" entry already exists.' );
		$menu->insertEntry( $entryHome );
	}

	/**
	 * @covers ::insertEntry
	 * @covers ::getEntries
	 */
	public function testInsertingAJavascriptOnlyEntry() {
		$menu = new Group( 'p-test' );
		$entryHome = SingleMenuEntry::create(
			'nearby',
			$this->nearbyComponent['text'],
			$this->nearbyComponent['href'],
			$this->nearbyComponent['class']
		);
		$entryHome->setJSOnly();
		$menu->insertEntry( $entryHome );

		$expectedEntries = [
			[
				'name' => 'nearby',
				'components' => [
					[
						'tag-name' => 'a',
						'label' => $this->nearbyComponent['text'],
						'array-attributes' => [
							[
								'key' => 'href',
								'value' => $this->nearbyComponent['href'],
							],
							[
								'key' => 'data-mw',
								'value' => 'interface'
							],
						],
						'classes' => 'menu__item--nearby',
						'data-icon' => [
							'icon' => 'nearby',
						]
					]
				],
				'class' => 'skin-minerva-list-item-jsonly'
			],
		];

		$this->assertEquals( $expectedEntries, $menu->getEntries() );
	}

	/**
	 * @covers ::getEntryByName
	 * @covers ::search
	 */
	public function testGetEntryByName() {
		$menu = new Group( 'p-test' );
		$entryHome = SingleMenuEntry::create(
			'home',
			$this->homeComponent['text'],
			$this->homeComponent['href'],
			$this->homeComponent['class']
		);
		$menu->insertEntry( $entryHome );
		$this->assertInstanceOf( IMenuEntry::class, $menu->getEntryByName( 'home' ) );
	}

	/**
	 * @covers ::getEntryByName
	 * @covers ::search
	 */
	public function testGetEntryByNameException() {
		$menu = new Group( 'p-test' );
		$this->expectException( DomainException::class );
		$menu->getEntryByName( 'home' );
	}

}
