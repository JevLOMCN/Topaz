<?php

namespace phpunit\InputCheck;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\Extension\Math\InputCheck\LocalChecker;
use MediaWiki\Extension\Math\InputCheck\MathoidChecker;
use MediaWiki\Extension\Math\InputCheck\RestbaseChecker;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Tests\Unit\MockServiceDependenciesTrait;
use MediaWikiIntegrationTestCase;
use Message;
use WANObjectCache;

/**
 * @method InputCheckFactory newServiceInstance(string $serviceClass, array $parameterOverrides)
 * @covers \MediaWiki\Extension\Math\InputCheck\InputCheckFactory
 */
class InputCheckFactoryTest extends MediaWikiIntegrationTestCase {

	use MockServiceDependenciesTrait;

	private $fakeHTTP;
	private $fakeWAN;

	protected function setUp(): void {
		parent::setUp();
		$this->fakeHTTP = $this->createMock( HttpRequestFactory::class );
		$this->fakeWAN = $this->createMock( WANObjectCache::class );
	}

	public function testNewMathoidChecker() {
		$checker = $this->newServiceInstance( InputCheckFactory::class, [
			'options' => new ServiceOptions( InputCheckFactory::CONSTRUCTOR_OPTIONS, [
				'MathMathMLUrl' => 'something',
				'MathTexVCService' => 'mathoid',
				'MathLaTeXMLTimeout' => 240
			] )
		] )
			->newMathoidChecker( 'FORMULA', 'TYPE', false );
		$this->assertInstanceOf( MathoidChecker::class, $checker );
	}

	public function testNewRestbaseChecker() {
		$checker = $this->newServiceInstance( InputCheckFactory::class, [] )
			->newRestbaseChecker( 'FORMULA', 'TYPE' );
		$this->assertInstanceOf( RestbaseChecker::class, $checker );
	}

	public function testNewLocalChecker() {
		$checker = $this->newServiceInstance( InputCheckFactory::class, [] )
			->newLocalChecker( 'FORMULA', 'tex' );
		$this->assertInstanceOf( LocalChecker::class, $checker );
	}

	public function testInvalidLocalChecker() {
		$myFactory = new InputCheckFactory(
			new ServiceOptions( InputCheckFactory::CONSTRUCTOR_OPTIONS, [
				'MathMathMLUrl' => 'something',
				'MathTexVCService' => 'local',
				'MathLaTeXMLTimeout' => 240
			] ),
			$this->fakeWAN,
			$this->fakeHTTP,
			LoggerFactory::getInstance( 'Math' )
		);
		$checker = $myFactory->newLocalChecker( 'FORMULA', 'INVALIDTYPE' );
		$this->assertInstanceOf( LocalChecker::class, $checker );
		$this->assertInstanceOf( Message::class, $checker->getError() );
		$this->assertFalse( $checker->isValid() );
	}

	public function testNewDefaultChecker() {
		$checker = $this->newServiceInstance( InputCheckFactory::class, [] )
			->newDefaultChecker( 'FORMULA', 'TYPE' );
		$this->assertInstanceOf( RestbaseChecker::class, $checker );
	}

	public function testNewMLocalCheckerDefault() {
		$myFactory = new InputCheckFactory(
			new ServiceOptions( InputCheckFactory::CONSTRUCTOR_OPTIONS, [
				'MathMathMLUrl' => 'something',
				'MathTexVCService' => 'local',
				'MathLaTeXMLTimeout' => 240
			] ),
			$this->fakeWAN,
			$this->fakeHTTP,
			LoggerFactory::getInstance( 'Math' )
		);

		$checker = $myFactory->newDefaultChecker( 'FORMULA', 'tex' );
		$this->assertInstanceOf( LocalChecker::class, $checker );
	}

	public function testMathoidCheckerInDefault() {
		$myFactory = new InputCheckFactory(
			new ServiceOptions( InputCheckFactory::CONSTRUCTOR_OPTIONS, [
				'MathMathMLUrl' => 'something',
				'MathTexVCService' => 'mathoid',
				'MathLaTeXMLTimeout' => 240
			] ),
			$this->fakeWAN,
			$this->fakeHTTP,
			LoggerFactory::getInstance( 'Math' )
		);

		$checker = $myFactory->newDefaultChecker( 'FORMULA', 'TYPE' );
		$this->assertInstanceOf( MathoidChecker::class, $checker );
	}
}
