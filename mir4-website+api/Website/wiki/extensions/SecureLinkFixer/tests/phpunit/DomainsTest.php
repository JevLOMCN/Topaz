<?php
/**
 * Copyright (C) 2019 Kunal Mehta <legoktm@debian.org>
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

use MediaWiki\SecureLinkFixer\ListFetcher;
use MediaWikiIntegrationTestCase;

/**
 * Integration test and sanity check for domains.php
 * @coversNothing
 */
class DomainsTest extends MediaWikiIntegrationTestCase {

	public function testReproducibility() {
		$domains = file_get_contents( __DIR__ . '/../../domains.php' );
		preg_match( '/mozilla\/gecko-dev@([0-9a-f]*?) \((.*?)\)/', $domains, $matches );
		$this->assertCount( 3, $matches );
		[ , $rev, $date ] = $matches;
		$lf = new ListFetcher();
		$expected = $lf->fetchList( $rev, $date );
		$this->assertSame( $expected, $domains );
	}

	public function testDomains() {
		$domains = require __DIR__ . '/../../domains.php';
		$this->assertIsArray( $domains );
		// Some arbitrary number as a sanity check
		$this->assertGreaterThan( 50000, count( $domains ) );
		foreach ( $domains as $domain => $subdomain ) {
			$this->assertMatchesRegularExpression( '/^[A-z0-9\-\.]*$/', $domain );
			$this->assertTrue( $subdomain === 0 || $subdomain === 1 );
		}
	}
}
