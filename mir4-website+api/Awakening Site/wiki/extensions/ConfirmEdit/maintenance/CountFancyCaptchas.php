<?php
/**
 * Counts the number of fancy captchas remaining.
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
if ( getenv( 'MW_INSTALL_PATH' ) ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
} else {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\FancyCaptcha;
use MediaWiki\Extension\ConfirmEdit\Hooks;

/**
 * Maintenance script that counts the number of captchas remaining.
 *
 * @ingroup Maintenance
 */
class CountFancyCaptchas extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Counts the number of fancy captchas in storage" );
		$this->addOption(
			'captchastoragedir',
			'Overrides the value of $wgCaptchaStorageDirectory',
			false,
			true
		);
		$this->requireExtension( "FancyCaptcha" );
	}

	public function execute() {
		$instance = Hooks::getInstance();
		if ( !( $instance instanceof FancyCaptcha ) ) {
			$this->fatalError( "\$wgCaptchaClass is not FancyCaptcha.\n", 1 );
		}

		// Overrides $wgCaptchaStorageDirectory for this script run
		if ( $this->hasOption( 'captchastoragedir' ) ) {
			global $wgCaptchaStorageDirectory;
			$wgCaptchaStorageDirectory = $this->getOption( 'captchastoragedir' );
		}

		$countAct = $instance->getCaptchaCount();
		$this->output( "Current number of stored captchas is $countAct.\n" );
	}
}

$maintClass = CountFancyCaptchas::class;
require_once RUN_MAINTENANCE_IF_MAIN;
