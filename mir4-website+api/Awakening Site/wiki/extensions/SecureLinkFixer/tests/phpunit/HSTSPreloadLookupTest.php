<?php
/**
 * Copyright (C) 2018 Kunal Mehta <legoktm@debian.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace MediaWiki\SecureLinkFixer\Test;

use MediaWiki\SecureLinkFixer\HSTSPreloadLookup;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\SecureLinkFixer\HSTSPreloadLookup
 */
class HSTSPreloadLookupTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider provideIsPreloaded
	 */
	public function testIsPreloaded( $host, $expected ) {
		$lookup = new HSTSPreloadLookup( 'dummy' );
		TestingAccessWrapper::newFromObject( $lookup )->domains = [
			// TLD
			'foobar' => 1,
			'secure-example.org' => 1,
			// includeSubdomains=false
			'insecure-subdomains-example.org' => 0,
			// Subdomain is secure, root domain isn't
			'secure.insecure-example.org' => 1,
		];
		$this->assertSame( $expected, $lookup->isPreloaded( $host ) );
	}

	public static function provideIsPreloaded() {
		return [
			[ 'example.foobar', true ],
			[ 'secure-example.org', true ],
			[ 'subdomain.secure-example.org', true ],
			[ 'insecure-subdomains-example.org', true ],
			[ 'subdomain.insecure-subdomains-example.org', false ],
			[ 'secure.insecure-example.org', true ],
			[ 'insecure-example.org', false ],
			[ 'not-preloaded.org', false ],
			[ 'pathological.case.that.is.not.preloaded.org', false ],
		];
	}
}
