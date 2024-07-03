<?php

namespace PageImages\Tests\Hooks;

use File;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Linker\LinksMigration;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWikiIntegrationTestCase;
use PageImages\Hooks\ParserFileProcessingHookHandlers;
use PageImages\PageImageCandidate;
use PageImages\PageImages;
use Parser;
use ParserOptions;
use RepoGroup;
use WANObjectCache;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \PageImages\Hooks\ParserFileProcessingHookHandlers
 *
 * @group PageImages
 *
 * @license WTFPL
 * @author Thiemo Kreuz
 */
class ParserFileProcessingHookHandlersTest extends MediaWikiIntegrationTestCase {

	public function setUp(): void {
		parent::setUp();

		// Force LinksUpdateHookHandler::getPageImageCandidates to look at all
		// sections.
		$this->setMwGlobals( 'wgPageImagesLeadSectionOnly', false );
	}

	/**
	 * @param array[] $images
	 *
	 * @return Parser
	 */
	private function getParser( array $images ) {
		$parser = $this->getServiceContainer()->getParser();
		$title = Title::newFromText( 'test' );
		$options = ParserOptions::newFromAnon();
		$parser->startExternalParse( $title, $options, Parser::OT_HTML );
		$parser->getOutput()->setExtensionData( 'pageImages', $images );
		return $parser;
	}

	private function getHtml( $indexes, $nonLeadIndex = INF ) {
		$html = '';
		$doneSectionBreak = false;
		foreach ( $indexes as $index ) {
			if ( $index >= $nonLeadIndex && !$doneSectionBreak ) {
				$html .= '<mw:editsection page="Test" section="1"/>';
				$doneSectionBreak = true;
			}
			$html .= "<!--MW-PAGEIMAGES-CANDIDATE-$index-->";
		}
		return $html;
	}

	/**
	 * Required to make RepoGroup::findFile in ParserFileProcessingHookHandlers::getScore return something.
	 * @return RepoGroup
	 */
	private function getRepoGroup() {
		$file = $this->createMock( File::class );
		// ugly hack to avoid all the unmockable crap in FormatMetadata
		$file->method( 'isDeleted' )
			->willReturn( true );

		$repoGroup = $this->createMock( RepoGroup::class );
		$repoGroup->method( 'findFile' )
			->willReturn( $file );

		return $repoGroup;
	}

	private function getHandler( $images ) {
		return new class ( $images ) extends ParserFileProcessingHookHandlers {
			private $images;
			private $isFreeMap;

			public function __construct( $images ) {
				$this->images = $images;
				foreach ( $images as $image ) {
					$this->isFreeMap[$image['filename']] = $image['isFree'];
				}
			}

			protected function isImageFree( $fileName ) {
				return $this->isFreeMap[$fileName] ?? false;
			}

			protected function getScore( PageImageCandidate $image, $position ) {
				return $this->images[$position]['score'];
			}
		};
	}

	/**
	 * @dataProvider provideDoParserAfterTidy
	 * @covers \PageImages\Hooks\ParserFileProcessingHookHandlers::onParserAfterTidy
	 */
	public function testDoParserAfterTidy(
		array $images,
		$expectedFreeFileName,
		$expectedNonFreeFileName
	) {
		$parser = $this->getParser( $images );
		$html = $this->getHtml( array_keys( $images ) );
		$handler = $this->getHandler( $images );
		$handler->onParserAfterTidy( $parser, $html );
		$properties = $parser->getOutput()->getPageProperties();

		if ( $expectedFreeFileName === null ) {
			$this->assertArrayNotHasKey( PageImages::PROP_NAME_FREE, $properties );
		} else {
			$this->assertSame( $expectedFreeFileName,
				$properties[PageImages::PROP_NAME_FREE] );
		}
		if ( $expectedNonFreeFileName === null ) {
			$this->assertArrayNotHasKey( PageImages::PROP_NAME, $properties );
		} else {
			$this->assertSame( $expectedNonFreeFileName, $properties[PageImages::PROP_NAME] );
		}
	}

	public static function provideDoParserAfterTidy() {
		return [
			// both images are non-free
			[
				[
					[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => false ],
					[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => false ],
				],
				null,
				'A.jpg'
			],
			// both images are free
			[
				[
					[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => true ],
					[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => true ],
				],
				'A.jpg',
				null
			],
			// one free (with a higher score), one non-free image
			[
				[
					[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => true ],
					[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => false ],
				],
				'A.jpg',
				null
			],
			// one non-free (with a higher score), one free image
			[
				[
					[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => false ],
					[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => true ],
				],
				'B.jpg',
				'A.jpg'
			]
		];
	}

	/**
	 * @dataProvider provideDoParserAfterTidy_lead
	 * @covers \PageImages\Hooks\ParserFileProcessingHookHandlers::onParserAfterTidy
	 */
	public function testDoParserAfterTidy_lead( $leadOnly ) {
		$this->setMwGlobals( 'wgPageImagesLeadSectionOnly', $leadOnly );
		$candidates = [
			[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => false ],
			[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => true ],
		];

		$parser = $this->getParser( $candidates );
		$html = $this->getHtml( array_keys( $candidates ), 1 );
		$handler = $this->getHandler( $candidates );
		$handler->onParserAfterTidy( $parser, $html );
		if ( $leadOnly ) {
			$this->assertNull(
				$parser->getOutput()->getPageProperty( PageImages::PROP_NAME_FREE ),
				'Only lead images are returned.' );
		} else {
			$this->assertIsString(
				$parser->getOutput()->getPageProperty( PageImages::PROP_NAME_FREE ),
				'All images are returned'
			);

		}
	}

	public static function provideDoParserAfterTidy_lead() {
		return [
			[ false ],
			[ true ]
		];
	}

	/**
	 * @dataProvider provideGetScore
	 */
	public function testGetScore( $image, $scoreFromTable, $position, $expected ) {
		$mock = TestingAccessWrapper::newFromObject(
			$this->getMockBuilder( ParserFileProcessingHookHandlers::class )
				->setConstructorArgs( [
					$this->getRepoGroup(),
					$this->createMock( WANObjectCache::class ),
					$this->createMock( HttpRequestFactory::class ),
					$this->createMock( IConnectionProvider::class ),
					$this->createMock( TitleFactory::class ),
					$this->createMock( LinksMigration::class ),
				] )
				->onlyMethods( [ 'scoreFromTable', 'fetchFileMetadata', 'getRatio', 'getDenylist' ] )
				->getMock()
		);
		$mock->method( 'scoreFromTable' )
			->willReturn( $scoreFromTable );
		$mock->method( 'getRatio' )
			->willReturn( 0 );
		$mock->method( 'getDenylist' )
			->willReturn( [ 'denylisted.jpg' => 1 ] );

		$score = $mock->getScore( PageImageCandidate::newFromArray( $image ), $position );
		$this->assertSame( $expected, $score );
	}

	public static function provideGetScore() {
		return [
			[
				[ 'filename' => 'A.jpg', 'handler' => [ 'width' => 100 ] ],
				100,
				0,
				// width score + ratio score + position score
				100 + 100 + 8
			],
			[
				[ 'filename' => 'A.jpg', 'fullwidth' => 100 ],
				50,
				1,
				// width score + ratio score + position score
				106
			],
			[
				[ 'filename' => 'A.jpg', 'fullwidth' => 100 ],
				50,
				2,
				// width score + ratio score + position score
				104
			],
			[
				[ 'filename' => 'A.jpg', 'fullwidth' => 100 ],
				50,
				3,
				// width score + ratio score + position score
				103
			],
			[
				[ 'filename' => 'denylisted.jpg', 'fullwidth' => 100 ],
				50,
				3,
				// denylist score
				- 1000
			],
			[
				[ 'filename' => 'A.jpg', 'frame' => [ 'class' => 'notpageimage' ] ],
				0,
				0,
				-1000
			],
		];
	}

	/**
	 * @dataProvider provideScoreFromTable
	 * @covers \PageImages\Hooks\ParserFileProcessingHookHandlers::scoreFromTable
	 */
	public function testScoreFromTable( array $scores, $value, $expected ) {
		/** @var ParserFileProcessingHookHandlers $handlerWrapper */
		$handlerWrapper = TestingAccessWrapper::newFromObject(
			new ParserFileProcessingHookHandlers(
				$this->getRepoGroup(),
				$this->createMock( WANObjectCache::class ),
				$this->createMock( HttpRequestFactory::class ),
				$this->createMock( IConnectionProvider::class ),
				$this->createMock( TitleFactory::class ),
				$this->createMock( LinksMigration::class )
			)
		);

		$score = $handlerWrapper->scoreFromTable( $value, $scores );
		$this->assertEquals( $expected, $score );
	}

	public static function provideScoreFromTable() {
		global $wgPageImagesScores;

		return [
			'no match' => [ [], 100, 0 ],
			'float' => [ [ 0.5 ], 0, 0.5 ],

			'always min when below range' => [ [ 200 => 2, 800 => 1 ], 0, 2 ],
			'always max when above range' => [ [ 200 => 2, 800 => 1 ], 1000, 1 ],

			'always min when below range (reversed)' => [ [ 800 => 1, 200 => 2 ], 0, 2 ],
			'always max when above range (reversed)' => [ [ 800 => 1, 200 => 2 ], 1000, 1 ],

			'min match' => [ [ 200 => 2, 400 => 3, 800 => 1 ], 200, 2 ],
			'above min' => [ [ 200 => 2, 400 => 3, 800 => 1 ], 201, 3 ],
			'second last match' => [ [ 200 => 2, 400 => 3, 800 => 1 ], 400, 3 ],
			'above second last' => [ [ 200 => 2, 400 => 3, 800 => 1 ], 401, 1 ],

			// These test cases use the default values from extension.json
			[ $wgPageImagesScores['width'], 100, -100 ],
			[ $wgPageImagesScores['width'], 119, -100 ],
			[ $wgPageImagesScores['width'], 300, 10 ],
			[ $wgPageImagesScores['width'], 400, 10 ],
			[ $wgPageImagesScores['width'], 500, 5 ],
			[ $wgPageImagesScores['width'], 600, 5 ],
			[ $wgPageImagesScores['width'], 601, 0 ],
			[ $wgPageImagesScores['width'], 999, 0 ],
			[ $wgPageImagesScores['galleryImageWidth'], 99, -100 ],
			[ $wgPageImagesScores['galleryImageWidth'], 100, 0 ],
			[ $wgPageImagesScores['galleryImageWidth'], 500, 0 ],
			[ $wgPageImagesScores['ratio'], 1, -100 ],
			[ $wgPageImagesScores['ratio'], 3, -100 ],
			[ $wgPageImagesScores['ratio'], 4, 0 ],
			[ $wgPageImagesScores['ratio'], 5, 0 ],
			[ $wgPageImagesScores['ratio'], 10, 5 ],
			[ $wgPageImagesScores['ratio'], 20, 5 ],
			[ $wgPageImagesScores['ratio'], 25, 0 ],
			[ $wgPageImagesScores['ratio'], 30, 0 ],
			[ $wgPageImagesScores['ratio'], 31, -100 ],
			[ $wgPageImagesScores['ratio'], 40, -100 ],

			'T212013' => [ $wgPageImagesScores['width'], 0, -100 ],
		];
	}

	/**
	 * @dataProvider provideIsFreeImage
	 * @covers \PageImages\Hooks\ParserFileProcessingHookHandlers::isImageFree
	 */
	public function testIsFreeImage( $fileName, $metadata, $expected ) {
		$mock = TestingAccessWrapper::newFromObject(
			$this->getMockBuilder( ParserFileProcessingHookHandlers::class )
				->setConstructorArgs( [
					$this->getRepoGroup(),
					$this->createMock( WANObjectCache::class ),
					$this->createMock( HttpRequestFactory::class ),
					$this->createMock( IConnectionProvider::class ),
					$this->createMock( TitleFactory::class ),
					$this->createMock( LinksMigration::class ),
				] )
				->onlyMethods( [ 'fetchFileMetadata' ] )
				->getMock()
		);
		$mock->method( 'fetchFileMetadata' )
			->willReturn( $metadata );
		/** @var ParserFileProcessingHookHandlers $mock */
		$this->assertSame( $expected, $mock->isImageFree( $fileName ) );
	}

	public static function provideIsFreeImage() {
		return [
			[ 'A.jpg', [], true ],
			[ 'A.jpg', [ 'NonFree' => [ 'value' => '0' ] ], true ],
			[ 'A.jpg', [ 'NonFree' => [ 'value' => 0 ] ], true ],
			[ 'A.jpg', [ 'NonFree' => [ 'value' => false ] ], true ],
			[ 'A.jpg', [ 'NonFree' => [ 'value' => 'something' ] ], false ],
			[ 'A.jpg', [ 'something' => [ 'value' => 'something' ] ], true ],
		];
	}
}
