<?php
/**
 * Copyright (C) 2018-2019 Kunal Mehta <legoktm@debian.org>
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

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/includes/libs/StaticArrayWriter.php";
require_once __DIR__ . '/../includes/ListFetcher.php';

/**
 * Downloads Mozilla's HSTS preload list and builds it into a PHP file.
 *
 * We explicitly don't use Maintenance here so that this script
 * can be run without needing all of MediaWiki to be installed.
 */
function main() {
	$lf = new ListFetcher( static function ( $text ) {
		echo $text;
	} );
	[ $rev, $date ] = $lf->getLatestInfo();
	$code = $lf->fetchList( $rev, $date );
	file_put_contents( __DIR__ . '/../domains.php', $code );
	echo "Updated domains.php\n";
}

main();
