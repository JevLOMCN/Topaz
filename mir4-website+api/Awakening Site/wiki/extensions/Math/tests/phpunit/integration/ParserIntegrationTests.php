<?php

namespace MediaWiki\Extension\Math\Tests;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\Extension\Math\Render\RendererFactory;
use MediaWiki\Page\PageReferenceValue;
use MediaWikiIntegrationTestCase;
use ParserOptions;
use Psr\Log\NullLogger;

/**
 * @group Database
 * @covers \MediaWiki\Extension\Math\HookHandlers\ParserHooksHandler
 * @coversDefaultClass \MediaWiki\Extension\Math\HookHandlers\ParserHooksHandler
 */
class ParserIntegrationTests extends MediaWikiIntegrationTestCase {

	private function setupDummyRendering() {
		$this->setMwGlobals( 'wgMathValidModes', [ MathConfig::MODE_SOURCE, MathConfig::MODE_LATEXML ] );
		$this->mergeMwGlobalArrayValue( 'wgDefaultUserOptions', [ 'math' => MathConfig::MODE_SOURCE ] );
		$this->setService( 'Math.RendererFactory', new class(
			new ServiceOptions( RendererFactory::CONSTRUCTOR_OPTIONS, [
				'MathoidCli' => false,
				'MathEnableExperimentalInputFormats' => false,
				'MathValidModes' => [ MathConfig::MODE_SOURCE ],
			] ),
			$this->getServiceContainer()->getUserOptionsLookup(),
			new NullLogger()
		) extends RendererFactory {
			public function getRenderer(
				string $tex,
				array $params = [],
				string $mode = MathConfig::MODE_MATHML
			): MathRenderer {
				return new class( $mode, $tex, $params ) extends MathRenderer {
					public function __construct( $mode, $tex = '', $params = [] ) {
						parent::__construct( $tex, $params );
						$this->mode = $mode;
					}

					public function render() {
						return true;
					}

					public function checkTeX() {
						return true;
					}

					public function getHtmlOutput() {
						return "<render>$this->mode:$this->tex</render>";
					}

					protected function getMathTableName() {
						return 'whatever';
					}
				};
			}
		} );
	}

	/**
	 * @covers ::onParserOptionsRegister
	 */
	public function testMathParserOption() {
		$user = $this->getTestUser()->getUserIdentity();
		$defaultMode = $this->getServiceContainer()->getUserOptionsLookup()->getOption( $user, 'math' );
		$this->assertSame( $defaultMode, ParserOptions::newFromUser( $user )->getOption( 'math' ) );
		// 3 corresponds to 'source', see Hooks::mathModeToString
		$this->getServiceContainer()->getUserOptionsManager()->setOption( $user, 'math', 3 );
		$this->assertSame( MathConfig::MODE_SOURCE, ParserOptions::newFromUser( $user )->getOption( 'math' ) );
	}

	public function testParserCacheIntegration() {
		$this->setupDummyRendering();

		$page = $this->getExistingTestPage( __METHOD__ );
		$this->assertStatusGood(
			$this->editPage( $page, '<math>TEST_FORMULA</math>' ),
			'Sanity: edited page'
		);

		$parserOutputAccess = $this->getServiceContainer()->getParserOutputAccess();

		// source was set as a default, so the rendering will be shared with
		// canonical rendering produced by page edit
		$parserOptions1 = ParserOptions::newCanonical( 'canonical' );
		$parserOptions1->setOption( 'math', MathConfig::MODE_SOURCE );
		$render = $parserOutputAccess->getCachedParserOutput( $page, $parserOptions1 );
		$this->assertNotNull( $render );
		$this->assertStringContainsString( "<render>source:TEST_FORMULA</render>", $render->getText() );

		// Now render with 'mathml' and make sure we didn't get the cached output
		$parserOptions2 = ParserOptions::newCanonical( 'canonical' );
		$parserOptions2->setOption( 'math', MathConfig::MODE_MATHML );
		$this->assertNull( $parserOutputAccess->getCachedParserOutput( $page, $parserOptions2 ) );
		$renderStatus = $parserOutputAccess->getParserOutput( $page, $parserOptions2 );
		$this->assertStatusGood( $renderStatus );
		$this->assertStringContainsString(
			"<render>mathml:TEST_FORMULA</render>",
			$renderStatus->getValue()->getText()
		);

		// Fetch from cache with source
		$cachedWithDummy1 = $parserOutputAccess->getCachedParserOutput( $page, $parserOptions1 );
		$this->assertNotNull( $cachedWithDummy1 );
		$this->assertStringContainsString(
			"<render>source:TEST_FORMULA</render>",
			$cachedWithDummy1->getText()
		);

		// Fetch from cache with mathml
		$cachedWithDummy2 = $parserOutputAccess->getCachedParserOutput( $page, $parserOptions2 );
		$this->assertNotNull( $cachedWithDummy2 );
		$this->assertStringContainsString(
			"<render>mathml:TEST_FORMULA</render>",
			$cachedWithDummy2->getText()
		);
	}

	public function testMathInLink() {
		$this->setupDummyRendering();
		$po = ParserOptions::newFromAnon();
		$po->setOption( 'math', MathConfig::MODE_SOURCE );
		$res = $this->getServiceContainer()
			->getParser()
			->parse(
				'[[test|<math>formula</math>]]',
				PageReferenceValue::localReference( NS_MAIN, __METHOD__ ),
				$po
			)
			->getText();
		$this->assertStringMatchesFormat( '%A<a%S><render>mathml:formula</render></a>%A', $res );
	}
}
