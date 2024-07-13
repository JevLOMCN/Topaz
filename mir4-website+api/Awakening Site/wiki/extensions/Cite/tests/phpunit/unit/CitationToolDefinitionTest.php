<?php

namespace Cite\Tests\Unit;

use Cite\ResourceLoader\CitationToolDefinition;
use MediaWiki\ResourceLoader\Context;
use Message;

/**
 * @covers \Cite\ResourceLoader\CitationToolDefinition
 * @license GPL-2.0-or-later
 */
class CitationToolDefinitionTest extends \MediaWikiUnitTestCase {

	public function testGetScript() {
		$context = $this->createResourceLoaderContext();

		$expected = [
			[ 'name' => 'no-message', 'title' => 'Hard-coded title' ],
			[ 'name' => 'missing-message', 'title' => 'missing-message' ],
			[ 'name' => 'n', 'title' => 't' ],
		];
		$this->assertSame(
			've.ui.mwCitationTools = ' . json_encode( $expected ) . ';',
			CitationToolDefinition::makeScript( $context )
		);
	}

	private function createResourceLoaderContext(): Context {
		$definition = [
			// We expect broken and incomplete entries to be skipped
			[],
			[ 'name' => '' ],

			[ 'name' => 'no-message', 'title' => 'Hard-coded title' ],
			[ 'name' => 'missing-message' ],
			[ 'name' => 'n' ],
		];

		$msg = $this->createMock( Message::class );
		$msg->method( 'inContentLanguage' )
			->willReturnSelf();
		$msg->method( 'plain' )
			->willReturn( json_encode( $definition ) );
		$msg->method( 'text' )
			->willReturn( 't' );

		$disabled = $this->createMock( Message::class );
		$disabled->method( 'isDisabled' )->willReturn( true );

		$context = $this->createStub( Context::class );
		$context->method( 'msg' )
			->willReturnMap( [
				[ 'cite-tool-definition.json', $msg ],
				[ 'visualeditor-cite-tool-definition.json', $msg ],
				[ 'visualeditor-cite-tool-name-missing-message', $disabled ],
				[ 'visualeditor-cite-tool-name-n', $msg ]
			] );
		$context->method( 'encodeJson' )->willReturnCallback( 'json_encode' );
		return $context;
	}

}
