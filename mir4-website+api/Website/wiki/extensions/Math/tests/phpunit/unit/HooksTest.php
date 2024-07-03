<?php
namespace MediaWiki\Extension\Math\Tests;

use ExtensionRegistry;
use MediaWiki\Extension\Math\Hooks;
use MediaWiki\Settings\Config\ArrayConfigBuilder;
use MediaWiki\Settings\Config\PhpIniSink;
use MediaWiki\Settings\SettingsBuilder;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\Hooks
 */
class HooksTest extends MediaWikiUnitTestCase {

	public static function provideOnConfig() {
		$defaults = [
			'MathFullRestbaseURL' => 'https://wikimedia.org/api/rest_',
			'MathInternalRestbaseURL' => null,
			'MathUseInternalRestbasePath' => true,
			'VirtualRestConfig' => [],
		];

		yield 'defaults' => [
			$defaults,
			[
				'MathFullRestbaseURL' => 'https://wikimedia.org/api/rest_',
				'MathInternalRestbaseURL' => 'https://wikimedia.org/api/rest_',
			]
		];

		yield 'explicit MathFullRestbaseURL' => [
			[
				'MathFullRestbaseURL' => 'https://mywiki.test/rest/'
			] + $defaults,
			[
				'MathFullRestbaseURL' => 'https://mywiki.test/rest/',
				'MathInternalRestbaseURL' => 'https://mywiki.test/rest/',
			]
		];

		yield 'explicit MathInternalRestbaseURL' => [
			[
				'MathInternalRestbaseURL' => 'https://localhost:12345/rest/',
				'VirtualRestConfig' => [ 'modules' => [ 'restbase' => [ // should be ignored
						'url' => 'https://restbase.internal/rest/',
						'domain' => 'mywiki',
				] ] ]
			] + $defaults,
			[
				'MathFullRestbaseURL' => 'https://wikimedia.org/api/rest_',
				'MathInternalRestbaseURL' => 'https://localhost:12345/rest/',
			]
		];

		yield 'use VirtualRestConfig' => [
			[
				'VirtualRestConfig' => [ 'modules' => [ 'restbase' => [
					'url' => 'https://restbase.internal/rest/', // trailing slash should be normalized
					'domain' => 'mywiki.test',
				] ] ]
			] + $defaults,
			[
				'MathFullRestbaseURL' => 'https://wikimedia.org/api/rest_',
				'MathInternalRestbaseURL' => 'https://restbase.internal/rest/mywiki.test/',
			]
		];

		yield 'use VirtualRestConfig with icky domain' => [
			[
				'VirtualRestConfig' => [ 'modules' => [ 'restbase' => [
					'url' => 'https://restbase.internal/rest/',
					'domain' => 'https://mywiki.test:8080', // domain name should get extracted
				] ] ]
			] + $defaults,
			[
				'MathFullRestbaseURL' => 'https://wikimedia.org/api/rest_',
				'MathInternalRestbaseURL' => 'https://restbase.internal/rest/mywiki.test/',
			]
		];

		yield 'disabled MathUseInternalRestbasePath' => [
			[
				'MathInternalRestbaseURL' => 'https://localhost:12345/rest/', // should be ignored
				'VirtualRestConfig' => [ 'modules' => [ 'restbase' => [ // should be ignored
					'url' => 'https://restbase.internal/rest/',
					'domain' => 'mywiki',
				] ] ],
				'MathFullRestbaseURL' => 'https://mywiki.test/rest/',
				'MathUseInternalRestbasePath' => false,
			] + $defaults,
			[
				'MathFullRestbaseURL' => 'https://mywiki.test/rest/',
				'MathInternalRestbaseURL' => 'https://mywiki.test/rest/',
			]
		];
	}

	/**
	 * @dataProvider provideOnConfig
	 */
	public function testOnConfig( array $config, array $expected ) {
		$configSink = new ArrayConfigBuilder();
		$configSink->setMulti( $config );

		$settings = new SettingsBuilder(
			__DIR__,
			$this->createNoOpMock( ExtensionRegistry::class ),
			$configSink,
			$this->createNoOpMock( PhpIniSink::class )
		);

		Hooks::onConfig( [], $settings );

		$actual = $settings->getConfig();
		foreach ( $expected as $name => $value ) {
			$this->assertSame( $value, $actual->get( $name ) );
		}
	}

}
