<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaInterpreterNotFoundError;
use MediaWiki\Extension\Scribunto\Engines\LuaSandbox\LuaSandboxEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaStandalone\LuaStandaloneEngine;
use MediaWiki\Extension\Scribunto\ScribuntoEngineBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Parser;
use ParserOptions;
use PHPUnit\Framework\DataProviderTestSuite;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\WarningTestCase;
use PHPUnit\Util\Test;
use ReflectionClass;

/**
 * Trait that helps LuaEngineTestBase and LuaEngineUnitTestBase
 */
trait LuaEngineTestHelper {
	/** @var array[] */
	private static $engineConfigurations = [
		'LuaSandbox' => [
			'class' => LuaSandboxEngine::class,
			'memoryLimit' => 50000000,
			'cpuLimit' => 30,
			'allowEnvFuncs' => true,
			'maxLangCacheSize' => 30,
		],
		'LuaStandalone' => [
			'class' => LuaStandaloneEngine::class,
			'errorFile' => null,
			'luaPath' => null,
			'memoryLimit' => 50000000,
			'cpuLimit' => 30,
			'allowEnvFuncs' => true,
			'maxLangCacheSize' => 30,
		],
	];
	/** @var int[] */
	protected $templateLoadCounts = [];
	/** @var array */
	protected $extraModules = [];

	/**
	 * Create a PHPUnit test suite to run the test against all engines
	 * @param string $className Test class name
	 * @param string|null $group Engine to run with, or null to run all engines
	 * @return TestSuite
	 */
	protected static function makeSuite( $className, $group = null ) {
		$suite = new TestSuite;
		$suite->setName( $className );

		$class = new ReflectionClass( $className );

		foreach ( self::$engineConfigurations as $engineName => $opts ) {
			if ( $group !== null && $group !== $engineName ) {
				continue;
			}

			try {
				$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
				$parser->startExternalParse(
					Title::newMainPage(),
					ParserOptions::newFromAnon(),
					Parser::OT_HTML,
					true
				);
				$engineClass = $opts['class'];
				$engine = new $engineClass(
					self::$engineConfigurations[$engineName] + [ 'parser' => $parser ]
				);
				$parser->scribunto_engine = $engine;
				$engine->setTitle( $parser->getTitle() );
				$engine->getInterpreter();
			} catch ( LuaInterpreterNotFoundError $e ) {
				$suite->addTest(
					new LuaEngineTestSkip(
						$className, "interpreter for $engineName is not available"
					), [ 'Lua', $engineName ]
				);
				continue;
			}

			// Work around PHPUnit breakage: the only straightforward way to
			// get the data provider is to call Test::getProvidedData, but that
			// instantiates the class without passing any parameters to the
			// constructor. But we *need* that engine name.
			self::$staticEngineName = $engineName;

			$engineSuite = new DataProviderTestSuite;
			$engineSuite->setName( "$engineName: $className" );

			foreach ( $class->getMethods() as $method ) {
				if ( Test::isTestMethod( $method ) && $method->isPublic() ) {
					$name = $method->getName();
					$groups = Test::getGroups( $className, $name );
					$groups[] = 'Lua';
					$groups[] = $engineName;
					// Only run tests locally if the engine isn't the MW sandbox T125050
					if ( $engineName !== 'LuaSandbox' ) {
						$groups[] = 'Standalone';
					}
					$groups = array_unique( $groups );

					$data = Test::getProvidedData( $className, $name );
					if ( is_iterable( $data ) ) {
						// with @dataProvider
						$dataSuite = new DataProviderTestSuite(
							$className . '::' . $name
						);
						foreach ( $data as $k => $v ) {
							$dataSuite->addTest(
								new $className( $name, $v, $k, $engineName ),
								$groups
							);
						}
						$engineSuite->addTest( $dataSuite );
					} elseif ( $data === false ) {
						// invalid @dataProvider
						$engineSuite->addTest( new WarningTestCase(
							"The data provider specified for {$className}::$name is invalid."
						) );
					} else {
						// no @dataProvider
						$engineSuite->addTest(
							new $className( $name, [], '', $engineName ),
							$groups
						);
					}
				}
			}

			$suite->addTest( $engineSuite );
		}

		return $suite;
	}

	/**
	 * @return ScribuntoEngineBase
	 */
	protected function getEngine() {
		if ( !$this->engine ) {
			$services = MediaWikiServices::getInstance();
			$parser = $services->getParserFactory()->create();
			$options = ParserOptions::newFromAnon();
			$options->setTemplateCallback( [ $this, 'templateCallback' ] );
			$options->setTargetLanguage( $services->getLanguageFactory()->getLanguage( 'en' ) );
			$parser->startExternalParse( $this->getTestTitle(), $options, Parser::OT_HTML, true );

			// HACK
			if ( $this->engineName === 'LuaSandbox' ) {
				$class = LuaSandboxEngine::class;
			} elseif ( $this->engineName === 'LuaStandalone' ) {
				$class = LuaStandaloneEngine::class;
			}

			$this->engine = new $class(
				self::$engineConfigurations[$this->engineName] + [ 'parser' => $parser ]
			);
			$parser->scribunto_engine = $this->engine;
			$this->engine->setTitle( $parser->getTitle() );
		}
		return $this->engine;
	}

	/**
	 * @see Parser::statelessFetchTemplate
	 * @param Title $title
	 * @param Parser|false $parser
	 * @return array
	 */
	public function templateCallback( $title, $parser ) {
		$this->templateLoadCounts[$title->getFullText()] =
			( $this->templateLoadCounts[$title->getFullText()] ?? 0 ) + 1;
		if ( isset( $this->extraModules[$title->getFullText()] ) ) {
			return [
				'text' => $this->extraModules[$title->getFullText()],
				'finalTitle' => $title,
				'deps' => []
			];
		}

		$modules = $this->getTestModules();
		foreach ( $modules as $name => $fileName ) {
			$modTitle = Title::makeTitle( NS_MODULE, $name );
			if ( $modTitle->equals( $title ) ) {
				return [
					'text' => file_get_contents( $fileName ),
					'finalTitle' => $title,
					'deps' => []
				];
			}
		}
		return Parser::statelessFetchTemplate( $title, $parser );
	}

	/**
	 * Get the title used for unit tests
	 *
	 * @return Title
	 */
	protected function getTestTitle() {
		return Title::newMainPage();
	}

	/**
	 * Reset the cached engine. The next call to getEngine() will return a new
	 * object.
	 */
	protected function resetEngine() {
		$this->engine = null;
	}

}
