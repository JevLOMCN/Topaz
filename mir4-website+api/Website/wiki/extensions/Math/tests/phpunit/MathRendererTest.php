<?php

use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\Extension\Math\Tests\MathMockHttpTrait;

/**
 * Test the database access and core functionality of MathRenderer.
 *
 * @covers \MediaWiki\Extension\Math\MathRenderer
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathRendererTest extends MediaWikiIntegrationTestCase {
	use MathMockHttpTrait;

	private const SOME_TEX = "a+b";
	private const TEXVCCHECK_INPUT = '\sin x^2';
	private const TEXVCCHECK_OUTPUT = '\sin x^{2}';

	/**
	 * Checks the tex and hash functions
	 * @covers \MediaWiki\Extension\Math\MathRenderer::getTex
	 * @covers \MediaWiki\Extension\Math\MathRenderer::__construct
	 */
	public function testBasics() {
		$renderer = $this->getMockForAbstractClass( MathRenderer::class, [ self::SOME_TEX ] );
		/** @var MathRenderer $renderer */
		// check if the TeX input was corretly passed to the class
		$this->assertEquals( self::SOME_TEX, $renderer->getTex(), "test getTex" );
		$this->assertFalse( $renderer->isChanged(), "test if changed is initially false" );
	}

	/**
	 * Test behavior of writeCache() when nothing was changed
	 * @covers \MediaWiki\Extension\Math\MathRenderer::writeCache
	 */
	public function testWriteCacheSkip() {
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
				'writeToCache',
					'render',
					'getMathTableName',
					'getHtmlOutput'
				] )->getMock();
		$renderer->expects( $this->never() )->method( 'writeToCache' );
		/** @var MathRenderer $renderer */
		$renderer->writeCache();
	}

	/**
	 * Test behavior of writeCache() when values were changed.
	 * @covers \MediaWiki\Extension\Math\MathRenderer::writeCache
	 */
	public function testWriteCache() {
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
				'writeToCache',
					'render',
					'getMathTableName',
					'getHtmlOutput'
				] )->getMock();
		$renderer->expects( $this->never() )->method( 'writeToCache' );
		/** @var MathRenderer $renderer */
		$renderer->writeCache();
	}

	public function testSetPurge() {
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput'
				] )->getMock();
		/** @var MathRenderer $renderer */
		$renderer->setPurge();
		$this->assertTrue( $renderer->isPurge(), "Test purge." );
	}

	public function testDisableCheckingAlways() {
		$this->setupGoodMathRestBaseMockHttp();

		$this->setMwGlobals( "wgMathDisableTexFilter", 'never' );
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput',
				'readFromCache',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->never() )->method( 'readFromCache' );
		$renderer->expects( $this->once() )->method( 'setTex' )->with( self::TEXVCCHECK_OUTPUT );

		/** @var MathRenderer $renderer */
		$this->assertTrue( $renderer->checkTeX() );
		// now setTex should not be called again
		$this->assertTrue( $renderer->checkTeX() );
	}

	public function testDisableCheckingNever() {
		$this->setMwGlobals( "wgMathDisableTexFilter", 'always' );
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput',
				'readFromCache',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->never() )->method( 'readFromCache' );
		$renderer->expects( $this->never() )->method( 'setTex' );

		/** @var MathRenderer $renderer */
		$this->assertTrue( $renderer->checkTeX() );
	}

	public function testCheckingNewUnknown() {
		$this->setupGoodMathRestBaseMockHttp();

		$this->setMwGlobals( "wgMathDisableTexFilter", 'new' );
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput',
				'readFromCache',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->once() )->method( 'readFromCache' )
			->willReturn( false );
		$renderer->expects( $this->once() )->method( 'setTex' )->with( self::TEXVCCHECK_OUTPUT );

		/** @var MathRenderer $renderer */
		$this->assertTrue( $renderer->checkTeX() );
		// now setTex should not be called again
		$this->assertTrue( $renderer->checkTeX() );
	}

	public function testCheckingNewKnown() {
		$this->setupGoodMathRestBaseMockHttp();

		$this->setMwGlobals( "wgMathDisableTexFilter", 'new' );
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput',
				'readFromCache',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->once() )->method( 'readFromCache' )
			->willReturn( true );
		$renderer->expects( $this->never() )->method( 'setTex' );

		/** @var MathRenderer $renderer */
		$this->assertTrue( $renderer->checkTeX() );
		// we don't mark a object as checked even though we rely on the database cache
		// so readFromDatabase will be called again
		$this->assertTrue( $renderer->checkTeX() );
	}
}
