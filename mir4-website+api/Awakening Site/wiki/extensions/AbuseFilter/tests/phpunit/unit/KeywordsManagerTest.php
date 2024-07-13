<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @group AbuseFilterParser
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\KeywordsManager
 * @covers ::__construct
 */
class KeywordsManagerTest extends MediaWikiUnitTestCase {
	/**
	 * Convenience wrapper
	 * @return KeywordsManager
	 */
	private function getKeywordsManager(): KeywordsManager {
		return new KeywordsManager( $this->createMock( AbuseFilterHookRunner::class ) );
	}

	/**
	 * @covers ::getDisabledVariables
	 */
	public function testGetDisabledVariables() {
		$actual = $this->getKeywordsManager()->getDisabledVariables();
		// Value should be an associative array mapping var names to i18n strings
		$this->assertIsArray( $actual );
		$this->assertContainsOnly( 'string', $actual, true );
		$this->assertContainsOnly( 'string', array_keys( $actual ), true );
	}

	/**
	 * @covers ::getDeprecatedVariables
	 */
	public function testGetDeprecatedVariables() {
		$actual = $this->getKeywordsManager()->getDisabledVariables();
		// Value should be an associative array mapping old names to new names
		$this->assertIsArray( $actual );
		$this->assertContainsOnly( 'string', $actual, true );
		$this->assertContainsOnly( 'string', array_keys( $actual ), true );
	}

	/**
	 * @covers ::getDeprecatedVariables
	 */
	public function testGetDeprecatedVariables_hook() {
		$oldVarName = 'foobardeprecated';
		$newVarName = 'foobarpleaseuseme';
		$runner = $this->createMock( AbuseFilterHookRunner::class );
		$runner->method( 'onAbuseFilter_deprecatedVariables' )
			->willReturnCallback( static function ( &$val ) use ( $oldVarName, $newVarName ) {
				$val[$oldVarName] = $newVarName;
			} );
		$actual = ( new KeywordsManager( $runner ) )->getDeprecatedVariables();
		$this->assertArrayHasKey( $oldVarName, $actual );
		$this->assertSame( $newVarName, $actual[$oldVarName] );
	}

	/**
	 * @covers ::getBuilderValues
	 */
	public function testGetBuilderValues() {
		$actual = $this->getKeywordsManager()->getBuilderValues();
		// Value should be an associative array mapping old names to new names
		$this->assertIsArray( $actual );
		$this->assertContainsOnly( 'array', $actual, true );
		foreach ( $actual as $name => $section ) {
			$this->assertIsString( $name );
			$this->assertContainsOnly( 'string', $section, true, "Section $name" );
			$this->assertContainsOnly( 'string', array_keys( $section ), true, "Section $name" );
		}
	}

	/**
	 * @covers ::getBuilderValues
	 */
	public function testGetBuilderValues_hook() {
		$varName = 'magic_stuff';
		$varMessage = 'magic-stuff';
		$runner = $this->createMock( AbuseFilterHookRunner::class );
		$runner->method( 'onAbuseFilter_builder' )
			->willReturnCallback( static function ( &$val ) use ( $varName, $varMessage ) {
				$val['vars'][$varName] = $varMessage;
			} );
		$actual = ( new KeywordsManager( $runner ) )->getBuilderValues();
		$this->assertArrayHasKey( 'vars', $actual );
		$this->assertArrayHasKey( $varName, $actual['vars'] );
		$this->assertSame( $varMessage, $actual['vars'][$varName] );
	}

	/**
	 * @param string $varName
	 * @param bool $expected
	 * @covers ::isVarDisabled
	 * @dataProvider provideIsVarDisabled
	 */
	public function testIsVarDisabled( string $varName, bool $expected ) {
		$km = $this->getKeywordsManager();
		$this->assertSame( $expected, $km->isVarDisabled( $varName ) );
	}

	/**
	 * @return array[]
	 */
	public static function provideIsVarDisabled() {
		return [
			'disabled' => [ 'old_text', true ],
			'deprecated' => [ 'article_text', false ],
			'unknown' => [ 'vnaioygbaeuioryvvbrra', false ]
		];
	}

	/**
	 * @param string $varName
	 * @param bool $expected
	 * @covers ::isVarDeprecated
	 * @dataProvider provideIsVarDeprecated
	 */
	public function testIsVarDeprecated( string $varName, bool $expected ) {
		$km = $this->getKeywordsManager();
		$this->assertSame( $expected, $km->isVarDeprecated( $varName ) );
	}

	/**
	 * @return array[]
	 */
	public static function provideIsVarDeprecated() {
		return [
			'disabled' => [ 'old_text', false ],
			'deprecated' => [ 'article_text', true ],
			'unknown' => [ 'vnaioygbaeuioryvvbrra', false ]
		];
	}

	/**
	 * @covers ::isVarInUse
	 */
	public function testIsVarInUse() {
		// Add a new variable to avoid relying on what's currently valid
		$varName = 'my_new_var';
		$runner = $this->createMock( AbuseFilterHookRunner::class );
		$runner->method( 'onAbuseFilter_builder' )
			->willReturnCallback( static function ( &$val ) use ( $varName ) {
				$val['vars'][$varName] = 'some-message';
			} );
		$km = new KeywordsManager( $runner );
		$this->assertTrue( $km->isVarInUse( $varName ) );
	}

	/**
	 * @param string $varName
	 * @param bool $expected
	 * @covers ::varExists
	 * @dataProvider provideVarExists
	 */
	public function testVarExists( string $varName, bool $expected ) {
		$km = $this->getKeywordsManager();
		$this->assertSame( $expected, $km->varExists( $varName ) );
	}

	/**
	 * @param string $varName
	 * @param bool $exists
	 * @covers ::getMessageKeyForVar
	 * @dataProvider provideVarExists
	 */
	public function testGetMessageKeyForVar( string $varName, bool $exists ) {
		$km = $this->getKeywordsManager();
		if ( $exists ) {
			$val = $km->getMessageKeyForVar( $varName );
			$this->assertIsString( $val );
			$this->assertStringContainsString( 'abusefilter-edit-builder-vars', $val );
		} else {
			$this->assertNull( $km->getMessageKeyForVar( $varName ) );
		}
	}

	/**
	 * @return array[]
	 */
	public static function provideVarExists() {
		return [
			'disabled' => [ 'old_text', true ],
			'deprecated' => [ 'article_text', true ],
			'unknown' => [ 'vnaioygbaeuioryvvbrra', false ]
		];
	}

	/**
	 * @covers ::getVarsMappings
	 */
	public function testGetVarsMappings() {
		$actual = $this->getKeywordsManager()->getVarsMappings();
		// Value should be an associative array mapping var names to i18n strings
		$this->assertIsArray( $actual );
		$this->assertContainsOnly( 'string', $actual, true );
		$this->assertContainsOnly( 'string', array_keys( $actual ), true );
	}

	/**
	 * @covers ::getCoreVariables
	 */
	public function testGetCoreVariables() {
		$actual = $this->getKeywordsManager()->getCoreVariables();
		$this->assertIsArray( $actual );
		$this->assertContainsOnly( 'string', $actual, true );
	}
}
