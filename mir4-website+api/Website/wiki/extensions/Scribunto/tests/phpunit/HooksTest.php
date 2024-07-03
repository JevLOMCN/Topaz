<?php

namespace MediaWiki\Extension\Scribunto\Tests;

use MediaWiki\Extension\Scribunto\Hooks;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWikiCoversValidator;
use Monolog\Test\TestCase;

/**
 * @covers \MediaWiki\Extension\Scribunto\Hooks
 */
class HooksTest extends TestCase {
	use MediaWikiCoversValidator;

	public static function provideContentHandlerDefaultModelFor() {
		return [
			[ 'Module:Foo', CONTENT_MODEL_SCRIBUNTO, true ],
			[ 'Module:Foo/doc', null, true ],
			[ 'Module:Foo/styles.css', 'sanitized-css', true, 'sanitized-css' ],
			[ 'Module:Foo.json', CONTENT_MODEL_JSON, true ],
			[ 'Module:Foo/subpage.json', CONTENT_MODEL_JSON, true ],
			[ 'Main Page', null, true ],
		];
	}

	/**
	 * @dataProvider provideContentHandlerDefaultModelFor
	 */
	public function testContentHandlerDefaultModelFor( $name, $expected,
		$retVal, $before = null
	) {
		$title = Title::newFromText( $name );
		$model = $before;
		$ret = ( new Hooks(
			MediaWikiServices::getInstance()->getMainConfig()
		) )->onContentHandlerDefaultModelFor( $title, $model );
		$this->assertSame( $retVal, $ret );
		$this->assertSame( $expected, $model );
	}
}
