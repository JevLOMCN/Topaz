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

namespace MediaWiki\SecureLinkFixer;

use Benchmarker;
use MediaWiki\MediaWikiServices;
use const RUN_MAINTENANCE_IF_MAIN;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/includes/Benchmarker.php";

/**
 * Benchmark the current HSTSPreloadLookup implementation
 *
 * @codeCoverageIgnore
 */
class BenchLookup extends Benchmarker {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Benchmark for HSTSPreloadLookup' );
		$this->requireExtension( 'SecureLinkFixer' );
	}

	public function execute() {
		$lookup = MediaWikiServices::getInstance()->getService( 'HSTSPreloadLookup' );
		$domains = [
			// Need to traverse up one domain to find it
			'foobar.dev',
			// Directly in map
			'wikipedia.org',
			// Need to traverse up one domain to not find it
			'not-preloaded.com',
			// Need to traverse up 6 domains to not find it
			'pathological.case.that.is.not.preloaded.org',
		];
		$benches = [];
		foreach ( $domains as $domain ) {
			$benches[] = [
				'function' => [ $lookup, 'isPreloaded' ],
				'args' => [ $domain ],
			];
		}
		$this->bench( $benches );
	}
}

$maintClass = BenchLookup::class;
require_once RUN_MAINTENANCE_IF_MAIN;
