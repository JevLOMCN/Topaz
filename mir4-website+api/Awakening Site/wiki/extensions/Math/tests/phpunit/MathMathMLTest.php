<?php

use MediaWiki\Extension\Math\MathMathML;
use MediaWiki\Extension\Math\MathRestbaseInterface;
use MediaWiki\Extension\Math\Tests\MathMockHttpTrait;
use Wikimedia\TestingAccessWrapper;

/**
 * Test the MathML output format.
 *
 * @covers \MediaWiki\Extension\Math\MathMathML
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathMathMLTest extends MediaWikiIntegrationTestCase {
	use MathMockHttpTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( 'wgMathoidCli', false );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\MathMathML::__construct
	 */
	public function testMathMLConstructorWithPmml() {
		$mml = new MathMathML( '<mo>sin</mo>', [ 'type' => 'pmml' ] );
		$this->assertSame( 'pmml', $mml->getInputType() );
		$this->assertSame( '<math><mo>sin</mo></math>', $mml->getMathml() );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\MathMathML::__construct
	 */
	public function testMathMLConstructorWithInvalidType() {
		$mml = new MathMathML( '<mo>sin</mo>', [ 'type' => 'invalid' ] );
		$this->assertSame( 'tex', $mml->getInputType() );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\MathMathML::__construct
	 */
	public function testChangeRootElemts() {
		$mml = new MathMathML( '<mo>sin</mo>', [ 'type' => 'invalid' ] );
		$mml->setAllowedRootElements( [ 'a', 'b' ] );
		$this->assertSame( [ 'a', 'b' ], $mml->getAllowedRootElements() );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Invalid request.
	 * @covers \MediaWiki\Extension\Math\MathMathML::makeRequest
	 */
	public function testMakeRequestInvalid() {
		$url = 'http://example.com/invalid';
		$this->setMwGlobals( [
			'wgMathMathMLUrl' => $url,
		] );
		$this->installMockHttp(
			$this->makeFakeHttpRequest( 'Method Not Allowed', 405 )
		);

		$renderer = new MathMathML();
		$requestReturn = $renderer->makeRequest();
		$this->assertNull( $requestReturn->getValue(),
			"result value is null if MediaWiki\\Http\\HttpRequestFactory::post returns false." );
		$this->assertStatusError( 'math_invalidresponse', $requestReturn,
			"return an error if MediaWiki\\Http\\HttpRequestFactory::post returns false"
		);
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Valid request.
	 * @covers \MediaWiki\Extension\Math\MathMathML::makeRequest
	 */
	public function testMakeRequestSuccess() {
		$this->installMockHttp(
			$this->makeFakeHttpRequest( 'test content' )
		);
		$renderer = new MathMathML();

		$requestReturn = $renderer->makeRequest();
		$this->assertStatusGood( $requestReturn, 'successful call return' );
		$this->assertSame( 'test content', $requestReturn->getValue(), 'successful call' );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Timeout.
	 * @covers \MediaWiki\Extension\Math\MathMathML::makeRequest
	 */
	public function testMakeRequestTimeout() {
		$url = 'http://example.com/timeout';
		$this->setMwGlobals( [
			'wgMathMathMLUrl' => $url,
		] );
		$this->installMockHttp(
			$this->makeFakeTimeoutRequest()
		);
		$renderer = new MathMathML();

		$requestReturn = $renderer->makeRequest();
		$this->assertNull( $requestReturn->getValue(), "timeout call return" );
		$this->assertStatusError( 'math_timeout', $requestReturn,
			"timeout call errormessage"
		);
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Test case: Get PostData.
	 * @covers \MediaWiki\Extension\Math\MathMathML::makeRequest
	 */
	public function testMakeRequestGetPostData() {
		$this->installMockHttp( $this->makeFakeHttpRequest() );
		$renderer = $this->getMockBuilder( MathMathML::class )
			->onlyMethods( [ 'getPostData' ] )
			->getMock();
		$renderer->expects( $this->once() )->method( 'getPostData' );

		/** @var MathMathML $renderer */
		$renderer->makeRequest();
	}

	/**
	 * Checks if a String is a valid MathML element
	 * @covers \MediaWiki\Extension\Math\MathMathML::isValidMathML
	 */
	public function testisValidMathML() {
		$renderer = new MathMathML();
		$validSample = '<math>content</math>';
		$invalidSample = '<notmath />';
		$this->assertTrue( $renderer->isValidMathML( $validSample ),
			'test if math expression is valid mathml sample' );
		$this->assertFalse( $renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample' );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\MathMathML::isValidMathML
	 */
	public function testInvalidXml() {
		$renderer = new MathMathML();
		$invalidSample = '<mat';
		$this->assertFalse( $renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample' );
		$renderer->setXMLValidation( false );
		$this->assertTrue( $renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample' );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\MathMathML::correctSvgStyle
	 * @see https://phabricator.wikimedia.org/T132563
	 */
	public function testMathMLStyle() {
		$m = new MathMathML();
		$m->setSvg( 'style="vertical-align:-.505ex" height="2.843ex" width="28.527ex"' );
		$style = '';
		$m->correctSvgStyle( $style );
		$this->assertSame( 'vertical-align:-.505ex; height: 2.843ex; width: 28.527ex;', $style );
		$m->setSvg( 'style=" vertical-align:-.505ex; \n" height="2.843ex" width="28.527ex"' );
		$this->assertSame( 'vertical-align:-.505ex; height: 2.843ex; width: 28.527ex;', $style );
	}

	public function testWarning() {
		$this->setupGoodMathRestBaseMockHttp();
		$this->setMwGlobals( "wgMathDisableTexFilter", 'always' );

		$renderer = new MathMathML();
		$rbi = $this->getMockBuilder( MathRestbaseInterface::class )
			->onlyMethods( [ 'getWarnings', 'getSuccess' ] )
			->setConstructorArgs( [ '\sin x' ] )
			->getMock();
		$rbi->method( 'getWarnings' )->willReturn( [ (object)[ 'type' => 'mhchem-deprecation' ] ] );
		$rbi->method( 'getSuccess' )->willReturn( true );
		$renderer->setRestbaseInterface( $rbi );
		$renderer->render();
		$parser = $this->createMock( Parser::class );
		$parser->method( 'addTrackingCategory' )->willReturn( true );
		$parser->expects( $this->once() )
			->method( 'addTrackingCategory' )
			->with( 'math-tracking-category-mhchem-deprecation' );
		$renderer->addTrackingCategories( $parser );
	}

	public function testGetHtmlOutputQID() {
		$math = new MathMathML( "a+b", [ "qid" => "Q123" ] );
		$out = $math->getHtmlOutput();
		$this->assertStringContainsString( "data-qid=\"Q123\"", $out );
	}

	public function testGetHtmlOutputInvalidQID() {
		// test with not valid ID. An ID must match /Q\d+/
		$math = new MathMathML( "a+b", [ "qid" => "123" ] );
		$out = $math->getHtmlOutput();
		$this->assertStringNotContainsString( "data-qid", $out );
	}

	public function testEmpty() {
		// TODO: Once render returns status, we won't need TestingAccessWrapper anymore.
		$math = TestingAccessWrapper::newFromObject( new MathMathML( '' ) );
		$renderStatus = $math->doRender();
		$this->assertStatusError( 'math_empty_tex', $renderStatus );
	}
}
