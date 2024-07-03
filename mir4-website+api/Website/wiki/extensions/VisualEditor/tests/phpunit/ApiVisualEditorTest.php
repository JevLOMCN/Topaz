<?php

namespace MediaWiki\Extension\VisualEditor\Tests;

use ApiTestCase;
use ExtensionRegistry;
use HashConfig;
use MediaWiki\Extension\VisualEditor\ApiVisualEditor;
use Wikimedia\ScopedCallback;

/**
 * @group medium
 * @group Database
 *
 * @covers \MediaWiki\Extension\VisualEditor\ApiVisualEditor
 */
class ApiVisualEditorTest extends ApiTestCase {

	/** @var ScopedCallback|null */
	private $scopedCallback;

	protected function setUp(): void {
		parent::setUp();
		$this->scopedCallback = ExtensionRegistry::getInstance()->setAttributeForTest(
			'VisualEditorAvailableNamespaces',
			[ 'User' => true, 'Template_Talk' => true ]
		);
	}

	protected function tearDown(): void {
		$this->scopedCallback = null;
		parent::tearDown();
	}

	private function loadEditor( $overrideParams = [] ) {
		$params = array_merge( [
			'action' => 'visualeditor',
			'paction' => 'metadata',
			'page' => 'SomeTestPage',
		], $overrideParams );
		return $this->doApiRequestWithToken( $params );
	}

	public function testLoadEditorBasic() {
		$data = $this->loadEditor()[0]['visualeditor'];

		$this->assertSame( 'success', $data['result'] );

		$properties = [
			// When updating this, also update the sample response in
			// ve.init.mw.DesktopArticleTarget.test.js
			'result',
			'notices',
			'copyrightWarning',
			'checkboxesDef',
			'checkboxesMessages',
			'protectedClasses',
			'basetimestamp',
			'starttimestamp',
			'oldid',
			'blockinfo',
			'wouldautocreate',
			'canEdit',
			'content',
			'preloaded',
			// When updating this, also update the sample response in
			// ve.init.mw.DesktopArticleTarget.test.js
		];
		foreach ( $properties as $prop ) {
			$this->assertArrayHasKey( $prop, $data, "Result has key '$prop'" );
		}

		$this->assertSameSize( $properties, $data, "No other properties are expected" );
	}

	/**
	 * @dataProvider provideLoadEditorPreload
	 */
	public function testLoadEditorPreload( bool $useMyLanguage ) {
		$content = 'Some test page content';
		$pageTitle = 'Test VE preload';
		$this->editPage( $pageTitle, $content );
		$params = [
			'preload' => $useMyLanguage ? "Special:MyLanguage/$pageTitle" : $pageTitle,
			'paction' => 'wikitext',
		];
		// NB The page isn't actually translated, so we get the same content back.
		$this->assertSame(
			$content,
			$this->loadEditor( $params )[0]['visualeditor']['content']
		);
	}

	public static function provideLoadEditorPreload() {
		return [
			'load with preload content' => [ false ],
			'load with preload via Special:MyLanguage' => [ true ],
		];
	}

	public function testIsAllowedNamespace() {
		$config = new HashConfig( [ 'VisualEditorAvailableNamespaces' => [
			0 => true,
			1 => false,
		] ] );
		$this->assertTrue( ApiVisualEditor::isAllowedNamespace( $config, 0 ) );
		$this->assertFalse( ApiVisualEditor::isAllowedNamespace( $config, 1 ) );
	}

	public function testGetAvailableNamespaceIds() {
		$config = new HashConfig( [ 'VisualEditorAvailableNamespaces' => [
			0 => true,
			1 => false,
			-1 => true,
			999999 => true,
			2 => false,
			'Template' => true,
			'Foobar' => true,
		] ] );
		$this->assertSame(
			[ -1, 0, 10, 11 ],
			ApiVisualEditor::getAvailableNamespaceIds( $config )
		);
	}

	public function testIsAllowedContentType() {
		$config = new HashConfig( [ 'VisualEditorAvailableContentModels' => [
			'on' => true,
			'off' => false,
		] ] );
		$this->assertTrue( ApiVisualEditor::isAllowedContentType( $config, 'on' ) );
		$this->assertFalse( ApiVisualEditor::isAllowedContentType( $config, 'off' ) );
		$this->assertFalse( ApiVisualEditor::isAllowedContentType( $config, 'unknown' ) );
	}

}
