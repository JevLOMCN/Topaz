<?php

use MediaWiki\Extension\Notifications\Mapper\AbstractMapper;

/**
 * @covers \MediaWiki\Extension\Notifications\Mapper\AbstractMapper
 */
class AbstractMapperTest extends MediaWikiUnitTestCase {

	/**
	 * @return array [ 'mapper' => AbstractMapper, 'property' => ReflectionProperty ]
	 */
	public function testAttachListener() {
		$mapper = new EchoAbstractMapperStub();
		$mapper->attachListener( 'testMethod', 'key_a', static function () {
		} );

		$class = new ReflectionClass( EchoAbstractMapperStub::class );
		$property = $class->getProperty( 'listeners' );
		$property->setAccessible( true );
		$listeners = $property->getValue( $mapper );

		$this->assertArrayHasKey( 'testMethod', $listeners );
		$this->assertArrayHasKey( 'key_a', $listeners['testMethod'] );
		$this->assertIsCallable( $listeners['testMethod']['key_a'] );

		return [ 'mapper' => $mapper, 'property' => $property ];
	}

	public function testAttachListenerWithException() {
		$mapper = new EchoAbstractMapperStub();
		$this->expectException( InvalidArgumentException::class );
		$mapper->attachListener( 'nonExistingMethod', 'key_a', static function () {
		} );
	}

	/**
	 * @depends testAttachListener
	 */
	public function testGetMethodListeners( $data ) {
		/** @var AbstractMapper $mapper */
		$mapper = $data['mapper'];

		$listeners = $mapper->getMethodListeners( 'testMethod' );
		$this->assertArrayHasKey( 'key_a', $listeners );
		$this->assertIsCallable( $listeners['key_a'] );
	}

	/**
	 * @depends testAttachListener
	 */
	public function testGetMethodListenersWithException( $data ) {
		/** @var AbstractMapper $mapper */
		$mapper = $data['mapper'];

		$this->expectException( InvalidArgumentException::class );
		$mapper->getMethodListeners( 'nonExistingMethod' );
	}

	/**
	 * @depends testAttachListener
	 */
	public function testDetachListener( $data ) {
		/** @var AbstractMapper $mapper */
		$mapper = $data['mapper'];
		/** @var ReflectionProperty $property */
		$property = $data['property'];

		$mapper->detachListener( 'testMethod', 'key_a' );
		$listeners = $property->getValue( $mapper );
		$this->assertArrayHasKey( 'testMethod', $listeners );
		$this->assertTrue( !isset( $listeners['testMethod']['key_a'] ) );
	}

}
