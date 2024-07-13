<?php

namespace MediaWiki\Extension\Math\Tests;

use MediaWiki\Extension\Math\Rest\Popup;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityRevision;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @covers \MediaWiki\Extension\Math\Rest\Popup
 */
class PopupTest extends MathWikibaseConnectorTestFactory {

	use HandlerTestTrait;

	public function testNonExistingId() {
		$popupHandler = $this->getPopup();
		$response = $this->executeHandler( $popupHandler, $this->getRequest( '1', 'en' ) );
		$this->assertEquals( 400, $response->getStatusCode() );
		$data = json_decode( $response->getBody(), true );
		$this->assertEquals( 'Non-existing Wikibase ID.', $data[ 'message' ] );
	}

	public function testParameterSettingsSetup() {
		$popupHandler = $this->getPopup();
		$this->assertSame( [ 'qid' => [
			HANDLER::PARAM_SOURCE => 'path',
			ParamValidator::PARAM_TYPE => 'integer',
			ParamValidator::PARAM_REQUIRED => true,
		] ], $popupHandler->getParamSettings() );
	}

	public function testValidationExceptionForMalformedId() {
		$popupHandler = $this->getPopup();
		$this->initHandler( $popupHandler, $this->getRequest( 'Q1', 'en' ) );
		$this->expectException( HttpException::class );
		$this->validateHandler( $popupHandler );
	}

	public function testInvalidLanguage() {
		$languageNameUtilsMock = $this->createMock( LanguageNameUtils::class );
		$languageNameUtilsMock->expects( $this->once() )
			->method( 'isValidCode' )
			->with( 'tmp' )
			->willReturn( false );

		$popupHandler = $this->getPopup( null, $languageNameUtilsMock );
		$response = $this->executeHandler( $popupHandler, $this->getRequest( '1', 'tmp' ) );
		$this->assertEquals( 400, $response->getStatusCode() );
		$data = json_decode( $response->getBody(), true );
		$this->assertEquals( 'Invalid language code.', $data[ 'message' ] );
	}

	/**
	 * @dataProvider provideItemSetups
	 */
	public function testExistingId( bool $hasPart ) {
		$item = $this->setupMassEnergyEquivalenceItem( $hasPart );
		$popupHandler = $this->getPopup( null, null, $item );

		$request = $this->getRequest( '1', 'en' );
		$data = $this->executeHandlerAndGetBodyData( $popupHandler, $request );

		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'canonicalurl', $data );
		$this->assertArrayHasKey( 'fullurl', $data );
		$this->assertArrayHasKey( 'contentmodel', $data );
		$this->assertArrayHasKey( 'pagelanguagedir', $data );
		$this->assertArrayHasKey( 'pagelanguage', $data );
		$this->assertArrayHasKey( 'pagelanguagehtmlcode', $data );
		$this->assertArrayHasKey( 'extract', $data );

		$this->assertEquals( 'mass–energy equivalence', $data[ 'title' ] );
		$this->assertEquals( 'special/Q1', $data[ 'canonicalurl' ] );
		$this->assertEquals( 'special/Q1', $data[ 'fullurl' ] );
		$this->assertEquals( 'html', $data[ 'contentmodel' ] );

		$html = $data[ 'extract' ];

		// popup contains formula label and description
		$this->assertStringContainsString( self::TEST_ITEMS[ 'Q1' ][0], $html );
		$this->assertStringContainsString( self::TEST_ITEMS[ 'Q1' ][1], $html );

		// popup contains formula and labels of each element
		foreach ( self::TEST_ITEMS as $key => $part ) {
			if ( $key === 'Q1' ) {
				// the formula itself is not shown in the popup, only its elements
				continue;
			}
			$this->assertStringContainsString( $part[0], $html );
			$this->assertStringContainsString( self::getExpectedMathML( $part[2] ), $html );
		}
	}

	private function getRequest( $id, $lang ): RequestData {
		return new RequestData( [
			'pathParams' => [ 'qid' => $id ],
			'headers' => [
				'Accept-Language' => $lang
			]
		] );
	}

	private function getPopup(
		LanguageFactory $languageFactoryMock = null,
		LanguageNameUtils $languageNameUtilsMock = null,
		Item $item = null
	): Popup {
		$languageFactoryMock = $languageFactoryMock ?: $this->createMock( LanguageFactory::class );
		if ( !$languageNameUtilsMock ) {
			$languageNameUtilsMock = $this->createMock( LanguageNameUtils::class );
			$languageNameUtilsMock->method( 'isValidCode' )->willReturn( true );
		}
		$mathWikibaseConnectorMock = $item ?
			$this->getWikibaseConnectorWithExistingItems( new EntityRevision( $item ) ) :
			$this->getWikibaseConnector( $languageFactoryMock, $languageNameUtilsMock );

		$titleMock = $this->createMock( Title::class );
		$titleMock->method( 'getLocalURL' )->willReturn( 'special/Q1' );
		$titleMock->method( 'getFullURL' )->willReturn( 'special/Q1' );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->expects( $this->once() )
			->method( 'newFromText' )
			->willReturn( $titleMock );

		return new Popup( $mathWikibaseConnectorMock, $languageFactoryMock, $languageNameUtilsMock, $titleFactoryMock );
	}

	public static function provideItemSetups(): array {
		return [
			[ true ],
			[ false ],
		];
	}
}
