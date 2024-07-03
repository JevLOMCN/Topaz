<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaInterpreterNotFoundError;
use MediaWiki\Extension\Scribunto\Engines\LuaSandbox\LuaSandboxEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaStandalone\LuaStandaloneEngine;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWikiCoversValidator;
use Parser;
use ParserOptions;
use PHPUnit\Framework\TestCase;

/**
 * @group Lua
 * @group LuaSandbox
 * @group LuaStandalone
 * @coversNothing
 */
class LuaEnvironmentComparisonTest extends TestCase {
	use MediaWikiCoversValidator;

	/** @var array */
	public $sandboxOpts = [
		'memoryLimit' => 50000000,
		'cpuLimit' => 30,
		'allowEnvFuncs' => true,
	];
	/** @var array */
	public $standaloneOpts = [
		'errorFile' => null,
		'luaPath' => null,
		'memoryLimit' => 50000000,
		'cpuLimit' => 30,
		'allowEnvFuncs' => true,
	];

	/** @var LuaEngine[] */
	protected $engines = [];

	private function makeEngine( $class, $opts ) {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$options = ParserOptions::newFromAnon();
		$options->setTemplateCallback( [ $this, 'templateCallback' ] );
		$parser->startExternalParse( Title::newMainPage(), $options, Parser::OT_HTML, true );
		$engine = new $class ( [ 'parser' => $parser ] + $opts );
		$parser->scribunto_engine = $engine;
		$engine->setTitle( $parser->getTitle() );
		$engine->getInterpreter();
		return $engine;
	}

	protected function setUp(): void {
		parent::setUp();

		try {
			$this->engines['LuaSandbox'] = $this->makeEngine(
				LuaSandboxEngine::class, $this->sandboxOpts
			);
		} catch ( LuaInterpreterNotFoundError $e ) {
			$this->markTestSkipped( "LuaSandbox interpreter not available" );
		}

		try {
			$this->engines['LuaStandalone'] = $this->makeEngine(
				LuaStandaloneEngine::class, $this->standaloneOpts
			);
		} catch ( LuaInterpreterNotFoundError $e ) {
			$this->markTestSkipped( "LuaStandalone interpreter not available" );
		}
	}

	protected function tearDown(): void {
		foreach ( $this->engines as $engine ) {
			$engine->destroy();
		}
		$this->engines = [];
		parent::tearDown();
	}

	private function getGlobalEnvironment( $engine ) {
		static $script = <<<LUA
			xxxseen = {}
			function xxxGetTable( t )
				if xxxseen[t] then
					return 'table'
				end
				local ret = {}
				xxxseen[t] = ret
				for k, v in pairs( t ) do
					if k ~= '_G' and string.sub( k, 1, 3 ) ~= 'xxx' then
						if type( v ) == 'table' then
							ret[k] = xxxGetTable( v )
						elseif type( v ) == 'string'
							or type( v ) == 'number'
							or type( v ) == 'boolean'
							or type( v ) == 'nil'
						then
							ret[k] = v
						else
							ret[k] = type( v )
						end
					end
				end
				return ret
			end
			return xxxGetTable( _G )
LUA;
		$func = $engine->getInterpreter()->loadString( $script, 'script' );
		return $engine->getInterpreter()->callFunction( $func );
	}

	public function testGlobalEnvironment() {
		// Grab the first engine as the "standard"
		$firstEngine = reset( $this->engines );
		$firstName = key( $this->engines );
		$firstEnv = $this->getGlobalEnvironment( $firstEngine );

		// Test all others against it
		foreach ( $this->engines as $secondName => $secondEngine ) {
			if ( $secondName !== $firstName ) {
				$secondEnv = $this->getGlobalEnvironment( $secondEngine );
				$this->assertEquals( $firstEnv, $secondEnv,
					"Environments for $firstName and $secondName are not equivalent" );
			}
		}
	}
}
