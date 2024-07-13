<?php

namespace Cite\Tests\Unit;

use Cite\ReferenceMessageLocalizer;
use Language;

/**
 * @covers \Cite\ReferenceMessageLocalizer
 * @license GPL-2.0-or-later
 */
class ReferenceMessageLocalizerUnitTest extends \MediaWikiUnitTestCase {

	public function testLocalizeSeparators() {
		$mockLanguage = $this->createNoOpMock( Language::class, [ 'separatorTransformTable' ] );
		$mockLanguage->method( 'separatorTransformTable' )->willReturn( [ '.' => ',', '0' => '' ] );
		$messageLocalizer = new ReferenceMessageLocalizer( $mockLanguage );
		$this->assertSame( '10,0', $messageLocalizer->localizeSeparators( '10.0' ) );
	}

	public function testLocalizeDigits() {
		$mockLanguage = $this->createNoOpMock( Language::class, [ 'formatNumNoSeparators' ] );
		$mockLanguage->method( 'formatNumNoSeparators' )->willReturnArgument( 0 );
		$messageLocalizer = new ReferenceMessageLocalizer( $mockLanguage );
		$this->assertSame( '50005', $messageLocalizer->localizeDigits( '50005' ) );
	}

}
