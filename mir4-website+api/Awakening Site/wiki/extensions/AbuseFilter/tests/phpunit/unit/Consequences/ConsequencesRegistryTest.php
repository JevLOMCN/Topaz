<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Consequences;

use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Consequence;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWikiUnitTestCase;
use ReflectionClass;
use RuntimeException;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry
 */
class ConsequencesRegistryTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$hookRunner = $this->createMock( AbuseFilterHookRunner::class );
		$this->assertInstanceOf(
			ConsequencesRegistry::class,
			new ConsequencesRegistry( $hookRunner, [] )
		);
	}

	/**
	 * @covers ::getAllActionNames
	 */
	public function testGetAllActionNames() {
		$configActions = [ 'nothing' => false, 'rickroll' => true ];
		$customActionName = 'spell';
		$hookRunner = $this->createMock( AbuseFilterHookRunner::class );
		$hookRunner->method( 'onAbuseFilterCustomActions' )->willReturnCallback(
			static function ( &$actions ) use ( $customActionName ) {
				$actions[$customActionName] = 'strlen';
			}
		);
		$expected = [ 'nothing', 'rickroll', 'spell' ];
		$registry = new ConsequencesRegistry( $hookRunner, $configActions );
		$this->assertSame( $expected, $registry->getAllActionNames() );
	}

	/**
	 * @covers ::getAllEnabledActionNames
	 */
	public function testGetAllEnabledActionNames() {
		$configActions = [ 'nothing' => false, 'rickroll' => true ];
		$customActionName = 'spell';
		$hookRunner = $this->createMock( AbuseFilterHookRunner::class );
		$hookRunner->method( 'onAbuseFilterCustomActions' )->willReturnCallback(
			static function ( &$actions ) use ( $customActionName ) {
				$actions[$customActionName] = 'strlen';
			}
		);
		$expected = [ 'rickroll', 'spell' ];
		$registry = new ConsequencesRegistry( $hookRunner, $configActions );
		$this->assertSame( $expected, $registry->getAllEnabledActionNames() );
	}

	/**
	 * @covers ::getDangerousActionNames
	 */
	public function testGetDangerousActionNames() {
		// Cheat a bit
		$regReflection = new ReflectionClass( ConsequencesRegistry::class );
		$expected = $regReflection->getConstant( 'DANGEROUS_ACTIONS' );

		$registry = new ConsequencesRegistry( $this->createMock( AbuseFilterHookRunner::class ), [] );
		$this->assertSame( $expected, $registry->getDangerousActionNames() );
	}

	/**
	 * @covers ::getDangerousActionNames
	 */
	public function testGetDangerousActionNames_hook() {
		$extraDangerous = 'rickroll';
		$hookRunner = $this->createMock( AbuseFilterHookRunner::class );
		$hookRunner->method( 'onAbuseFilterGetDangerousActions' )->willReturnCallback(
			static function ( &$array ) use ( $extraDangerous ) {
				$array[] = $extraDangerous;
			}
		);
		$registry = new ConsequencesRegistry( $hookRunner, [] );
		$this->assertContains( $extraDangerous, $registry->getDangerousActionNames() );
	}

	/**
	 * @covers ::getCustomActions
	 * @covers ::validateCustomActions
	 */
	public function testGetCustomActions() {
		$customActionName = 'rickroll';
		$customAction = 'strlen';
		$hookRunner = $this->createMock( AbuseFilterHookRunner::class );
		$hookRunner->method( 'onAbuseFilterCustomActions' )->willReturnCallback(
			static function ( &$actions ) use ( $customActionName, $customAction ) {
				$actions[$customActionName] = $customAction;
			}
		);
		$registry = new ConsequencesRegistry( $hookRunner, [] );
		$this->assertSame( [ $customActionName => $customAction ], $registry->getCustomActions() );
	}

	/**
	 * @covers ::getCustomActions
	 * @covers ::validateCustomActions
	 */
	public function testGetCustomActions_invalidKey() {
		$hookRunner = $this->createMock( AbuseFilterHookRunner::class );
		$hookRunner->method( 'onAbuseFilterCustomActions' )->willReturnCallback(
			function ( &$actions ) {
				$invalidKey = 42;
				$actions[$invalidKey] = $this->createMock( Consequence::class );
			}
		);
		$registry = new ConsequencesRegistry( $hookRunner, [] );
		$this->expectException( RuntimeException::class );
		$registry->getCustomActions();
	}

	/**
	 * @covers ::getCustomActions
	 * @covers ::validateCustomActions
	 */
	public function testGetCustomActions_invalidValue() {
		$hookRunner = $this->createMock( AbuseFilterHookRunner::class );
		$hookRunner->method( 'onAbuseFilterCustomActions' )->willReturnCallback(
			static function ( &$actions ) {
				$invalidValue = 42;
				$actions['myaction'] = $invalidValue;
			}
		);
		$registry = new ConsequencesRegistry( $hookRunner, [] );
		$this->expectException( RuntimeException::class );
		$registry->getCustomActions();
	}
}
