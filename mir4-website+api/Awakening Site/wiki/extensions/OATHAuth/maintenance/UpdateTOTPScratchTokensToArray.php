<?php
/**
 * Updates TOTP Recovery Codes to an array
 *
 * Usage: php updateTOTPScratchTokensToArray.php
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

namespace MediaWiki\Extension\OATHAuth\Maintenance;

use FormatJson;
use LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;

if ( getenv( 'MW_INSTALL_PATH' ) ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
} else {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Merged December 2020; part of REL1_36
 */
class UpdateTOTPScratchTokensToArray extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Script to update TOTP Recovery Codes to an array' );
		$this->requireExtension( 'OATHAuth' );
	}

	protected function doDBUpdates() {
		$dbw = MediaWikiServices::getInstance()
			->getDBLoadBalancerFactory()
			->getPrimaryDatabase( 'virtual-oathauth' );

		$res = $dbw->newSelectQueryBuilder()
			->select( [ 'id', 'data' ] )
			->from( 'oathauth_users' )
			->where( [ 'module' => 'totp' ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $res as $row ) {
			$data = FormatJson::decode( $row->data, true );

			$updated = false;
			foreach ( $data['keys'] as &$k ) {
				if ( is_string( $k['scratch_tokens'] ) ) {
					$k['scratch_tokens'] = explode( ',', $k['scratch_tokens'] );
					$updated = true;
				}
			}
			unset( $k );

			if ( !$updated ) {
				continue;
			}

			$dbw->newUpdateQueryBuilder()
				->update( 'oathauth_users' )
				->set( [ 'data' => FormatJson::encode( $data ) ] )
				->where( [ 'id' => $row->id ] )
				->caller( __METHOD__ )
				->execute();
		}

		$this->output( "Done.\n" );
		return true;
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return __CLASS__;
	}
}

$maintClass = UpdateTOTPScratchTokensToArray::class;
require_once RUN_MAINTENANCE_IF_MAIN;
