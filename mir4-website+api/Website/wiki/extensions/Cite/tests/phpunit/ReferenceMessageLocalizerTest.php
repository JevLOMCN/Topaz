<?php

namespace Cite\Tests;

use Cite\ReferenceMessageLocalizer;
use LanguageQqx;

/**
 * @covers \Cite\ReferenceMessageLocalizer
 * @license GPL-2.0-or-later
 */
class ReferenceMessageLocalizerTest extends \MediaWikiIntegrationTestCase {

	public function testMsg() {
		$localizer = new ReferenceMessageLocalizer( new LanguageQqx() );
		$this->assertSame(
			'(cite-desc)',
			$localizer->msg( 'cite-desc' )->plain() );
	}

}
