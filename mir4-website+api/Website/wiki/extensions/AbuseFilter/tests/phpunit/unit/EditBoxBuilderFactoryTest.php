<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use LogicException;
use MediaWiki\Extension\AbuseFilter\AbuseFilterPermissionManager;
use MediaWiki\Extension\AbuseFilter\EditBox\AceEditBoxBuilder;
use MediaWiki\Extension\AbuseFilter\EditBox\EditBoxBuilderFactory;
use MediaWiki\Extension\AbuseFilter\EditBox\PlainEditBoxBuilder;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\Authority;
use MediaWikiUnitTestCase;
use MessageLocalizer;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Editbox\EditBoxBuilderFactory
 */
class EditBoxBuilderFactoryTest extends MediaWikiUnitTestCase {

	/**
	 * @param bool $isCodeEditorLoaded
	 * @return EditBoxBuilderFactory
	 */
	private function getFactory( bool $isCodeEditorLoaded ): EditBoxBuilderFactory {
		return new EditBoxBuilderFactory(
			$this->createMock( AbuseFilterPermissionManager::class ),
			$this->createMock( KeywordsManager::class ),
			$isCodeEditorLoaded
		);
	}

	/**
	 * @covers ::__construct
	 * @covers ::newEditBoxBuilder
	 * @covers \MediaWiki\Extension\AbuseFilter\EditBox\EditBoxBuilder::__construct
	 * @dataProvider provideNewEditBoxBuilder
	 * @param bool $isCodeEditorLoaded
	 */
	public function testNewEditBoxBuilder( bool $isCodeEditorLoaded ) {
		$builder = $this->getFactory( $isCodeEditorLoaded )->newEditBoxBuilder(
			$this->createMock( MessageLocalizer::class ),
			$this->createMock( Authority::class ),
			$this->createMock( OutputPage::class )
		);
		$isCodeEditorLoaded
			? $this->assertInstanceOf( AceEditBoxBuilder::class, $builder )
			: $this->assertInstanceOf( PlainEditBoxBuilder::class, $builder );
	}

	public static function provideNewEditBoxBuilder(): array {
		return [
			[ true ],
			[ false ]
		];
	}

	/**
	 * @covers ::newPlainBoxBuilder
	 */
	public function testNewPlainBoxBuilder() {
		$this->assertInstanceOf(
			PlainEditBoxBuilder::class,
			$this->getFactory( false )->newPlainBoxBuilder(
				$this->createMock( MessageLocalizer::class ),
				$this->createMock( Authority::class ),
				$this->createMock( OutputPage::class )
			)
		);
	}

	/**
	 * @covers ::newAceBoxBuilder
	 */
	public function testNewAceBoxBuilder() {
		$this->assertInstanceOf(
			AceEditBoxBuilder::class,
			$this->getFactory( true )->newAceBoxBuilder(
				$this->createMock( MessageLocalizer::class ),
				$this->createMock( Authority::class ),
				$this->createMock( OutputPage::class )
			)
		);
	}

	/**
	 * @covers ::newAceBoxBuilder
	 */
	public function testNewAceBoxBuilder__invalid() {
		$this->expectException( LogicException::class );
		$this->getFactory( false )->newAceBoxBuilder(
			$this->createMock( MessageLocalizer::class ),
			$this->createMock( Authority::class ),
			$this->createMock( OutputPage::class )
		);
	}
}
