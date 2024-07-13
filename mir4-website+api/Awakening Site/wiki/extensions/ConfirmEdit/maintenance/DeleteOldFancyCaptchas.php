<?php
/**
 * Deletes fancy captchas from storage
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
use MediaWiki\Status\Status;

/**
 * Maintenance script that deletes old fancy captchas from storage
 *
 * @ingroup Maintenance
 */
class DeleteOldFancyCaptchas extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Deletes old fancy captchas from storage" );
		$this->addOption(
			"date",
			'Delete fancy captchas that were created before this date (e.g. 20170101000000)',
			true,
			true
		);
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
		$this->output( "Current number of captchas is $countAct.\n" );

		$backend = $instance->getBackend();
		$dir = $backend->getRootStoragePath() . '/' . $instance->getStorageDir();

		$filesToDelete = [];
		$deleteDate = $this->getOption( 'date' );
		foreach (
			$backend->getFileList( [ 'dir' => $dir, 'adviseStat' => true ] ) as $file
		) {
			$fullPath = $dir . '/' . $file;
			$timestamp = $backend->getFileTimestamp( [ 'src' => $fullPath ] );
			if ( $timestamp < $deleteDate ) {
				$filesToDelete[] = [ 'op' => 'delete', 'src' => $fullPath, ];
			}
		}

		$count = count( $filesToDelete );

		if ( !$count ) {
			$this->output( "No old fancy captchas to delete!\n" );
			return;
		}

		$this->output( "$count old fancy captchas to be deleted.\n" );

		$deletedCount = 0;
		foreach ( array_chunk( $filesToDelete, 1000 ) as $chunk ) {
			$ret = $backend->doQuickOperations( $chunk );

			if ( $ret->isOK() ) {
				$chunkCount = count( $chunk );
				$this->output( "$chunkCount...\n" );
				$deletedCount += $chunkCount;
			} else {
				$status = Status::wrap( $ret );
				$this->output( "Deleting old captchas errored.\n" );
				$this->output( $status->getWikiText( false, false, 'en' ) );
			}
		}

		$this->output( "$deletedCount old fancy captchas deleted.\n" );
	}
}

$maintClass = DeleteOldFancyCaptchas::class;
require_once RUN_MAINTENANCE_IF_MAIN;
