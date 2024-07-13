#!/usr/bin/env php
<?php
/**
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
 * @ingroup Maintenance
 */

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

// phpcs:disable MediaWiki.Files.ClassMatchesFilename.NotMatch
class WikiTexVcCli extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "This script checks if the input is valid texvc." .
			"For valid input, it returns a normalized texvc string, " .
			"otherwise the error code and detail are shown." );
		$this->addArg( 'input', 'The tex input to be checked', true );
		$this->addOption( 'chem', 'Set for chem input', false, false );
		$this->requireExtension( 'Math' );
	}

	/**
	 * @throws Exception
	 */
	public function execute() {
		$userInputTex = $this->getArg( 0 );
		$texvc = new MediaWiki\Extension\Math\WikiTexVC\TexVC();
		$options = [ 'usemhchem' => $this->getOption( 'chem' ) ];
		$result = $texvc->check( $userInputTex, $options );
		if ( $result['status'] !== '+' ) {
			$this->error( $result['status'] . $result['details'] );
		}
		$this->output( $result['output'] );
		$this->output( "\n" );
	}
}

$maintClass = WikiTexVcCli::class;
/** @noinspection PhpIncludeInspection */
require_once RUN_MAINTENANCE_IF_MAIN;
