<?php

use MediaWiki\Shell\CommandFactory;
use MediaWiki\SyntaxHighlight\SyntaxHighlight;
use Shellbox\Command\BoxedCommand;
use Shellbox\Command\BoxedResult;
use Shellbox\ShellboxError;

/**
 * @covers MediaWiki\SyntaxHighlight\SyntaxHighlight
 * @covers MediaWiki\SyntaxHighlight\Pygmentize
 */
class PygmentizeTest extends MediaWikiIntegrationTestCase {
	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( [
			// Run with the default useBundled=true
			'wgPygmentizePath' => false,
			// Silence wfWarn for the expected Shellbox error
			'wgDevelopmentWarnings' => false,
		] );
	}

	private function stubShellbox( ?BoxedResult $result, ?Exception $e ) {
		$factory = $this->createStub( CommandFactory::class );
		$command = new class ( $result, $e ) extends BoxedCommand {
			private $result;
			private $e;

			public function __construct( $result, $e ) {
				$this->result = $result;
				$this->e = $e;
			}

			public function execute(): BoxedResult {
				if ( $this->e ) {
					throw $this->e;
				}
				return $this->result;
			}
		};
		$factory->method( 'createBoxed' )->willReturn( $command );
		$this->setService( 'ShellCommandFactory', $factory );
	}

	public static function provideHighlight() {
		yield 'basic' => [
			( new BoxedResult )
					->stdout( '<div class="mw-highlight><code>x</code></div>' )
					->exitCode( 0 ),
			null,
			'<div class="mw-highlight mw-highlight-lang-json mw-content-ltr" dir="ltr"><code>x</code></div>'
		];
		yield 'pre-fallback for non-zero exit' => [
			( new BoxedResult )
					->stdout( 'Boo' )
					->exitCode( 42 ),
			null,
			'<div class="mw-highlight mw-highlight-lang-json mw-content-ltr" dir="ltr"><pre>"example"</pre></div>'
		];
		yield 'pre-fallback for network error (T292663)' => [
			null,
			new ShellboxError( 'Wazaaaa', 0 ),
			'<div class="mw-highlight mw-highlight-lang-json mw-content-ltr" dir="ltr"><pre>"example"</pre></div>'
		];
	}

	/**
	 * @dataProvider provideHighlight
	 */
	public function testHighlightBasic( ?BoxedResult $result, ?Exception $e, string $expect ) {
		$this->stubShellbox( $result, $e );

		$status = SyntaxHighlight::highlight( '"example"', 'json' );
		$this->assertSame( $expect, $status->getValue() );
	}
}
