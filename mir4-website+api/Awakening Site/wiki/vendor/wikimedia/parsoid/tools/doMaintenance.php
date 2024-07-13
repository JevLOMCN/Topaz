<?php
/**
 * We want to make this whole thing as seamless as possible to the
 * end-user. Unfortunately, we can't do _all_ of the work in the class
 * because A) included files are not in global scope, but in the scope
 * of their caller, and B) MediaWiki has way too many globals. So instead
 * we'll kinda fake it, and do the requires() inline. <3 PHP
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 */

// THIS IS A STRIPPED DOWN VERSION OF doMaintenance.php from mediawiki core.

if ( !defined( 'PARSOID_RUN_MAINTENANCE_IF_MAIN' ) ) {
	echo "This file must be included after Maintenance.php\n";
	exit( 1 );
}

if ( !$maintClass || !class_exists( $maintClass ) ) {
	echo "\$maintClass is not set or is set to a non-existent class.\n";
	exit( 1 );
}

// Get an object to start us off
/** @var Maintenance $maintenance */
$maintenance = new $maintClass();

// Basic setup checks and such
$maintenance->setup();

$maintenance->finalSetup();

// Set an appropriate locale (T291234)
// Matches core's Setup.php
putenv( "LC_ALL=" . setlocale( LC_ALL, 'C.UTF-8', 'C' ) );

$maintenance->validateParamsAndArgs();

// Do the work
try {
	$success = $maintenance->execute();
} catch ( Exception $ex ) {
	$success = false;
	while ( $ex ) {
		$cls = get_class( $ex );
		print "$cls from line {$ex->getLine()} of {$ex->getFile()}: {$ex->getMessage()}\n";
		print $ex->getTraceAsString() . "\n";
		$ex = $ex->getPrevious();
	}
}

// Exit with an error status if execute() returned false
if ( $success === false ) {
	exit( 1 );
}
