<?php

use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\StaticGadgetRepo;

/**
 * @covers \MediaWiki\Extension\Gadgets\GadgetRepo
 * @group Gadgets
 * @group Database
 */
class GadgetRepoTest extends MediaWikiIntegrationTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->editPage( 'MediaWiki:Gadget-x.css', '' );
		$this->editPage( 'MediaWiki:Gadget-x.js', '' );
	}

	/**
	 * @see GadgetTest::testGadgetWarnings
	 * @dataProvider provideValidationWarnings
	 */
	public function testValidationWarnings( array $options, array $expected ) {
		$gadget = new Gadget( $options + [ 'name' => 'example' ] );
		$repo = new StaticGadgetRepo( [] );

		$warnings = $repo->validationWarnings( $gadget );
		foreach ( $warnings as &$message ) {
			$message = $message->inLanguage( 'qqx' )->plain();
		}
		$this->assertArrayEquals( $expected, $warnings );
	}

	public static function provideValidationWarnings() {
		yield 'simple' => [
			[ 'pages' => [ 'MediaWiki:Gadget-x.css', 'MediaWiki:Gadget-x.js' ] ],
			[]
		];
		yield 'missing style' => [
			[ 'pages' => [ 'MediaWiki:Gadget-test.css' ] ],
			[
				'(gadgets-validate-nopage: MediaWiki:Gadget-test.css)'
			]
		];
		yield 'missing script' => [
			[ 'pages' => [ 'MediaWiki:Gadget-test.js' ] ],
			[
				'(gadgets-validate-nopage: MediaWiki:Gadget-test.js)'
			]
		];
		yield 'valid model' => [
			[ 'pages' => [ 'MediaWiki:Gadget-x.css' ], 'requiredContentModels' => [ 'wikitext' ] ],
			[]
		];
		yield 'invalid model' => [
			[ 'pages' => [ 'MediaWiki:Gadget-x.css' ], 'requiredContentModels' => [ 'wat' ] ],
			[
				'(gadgets-validate-invalidcontentmodels: wat, 1)'
			]
		];
		yield 'valid ns' => [
			[ 'pages' => [ 'MediaWiki:Gadget-x.css' ], 'requiredNamespaces' => [ '1' ] ],
			[]
		];
		yield 'invalid ns' => [
			[ 'pages' => [ 'MediaWiki:Gadget-x.css' ], 'requiredNamespaces' => [ '99999', '1', '88888' ] ],
			[
				'(gadgets-validate-invalidnamespaces: 99999(comma-separator)88888, 2)'
			]
		];
	}
}
