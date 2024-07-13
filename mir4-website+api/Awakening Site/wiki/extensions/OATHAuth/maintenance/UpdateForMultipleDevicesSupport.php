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
 * @file
 */

namespace MediaWiki\Extension\OATHAuth\Maintenance;

use FormatJson;
use LoggedUpdateMaintenance;
use MediaWiki\Extension\OATHAuth\OATHAuthServices;
use MediaWiki\MediaWikiServices;

if ( getenv( 'MW_INSTALL_PATH' ) ) {
	$IP = getenv( 'MW_INSTALL_PATH' );
} else {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * @author Taavi Väänänen <hi@taavi.wtf>
 */
class UpdateForMultipleDevicesSupport extends LoggedUpdateMaintenance {
	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'OATHAuth' );
		$this->setBatchSize( 500 );
	}

	protected function doDBUpdates() {
		$dbw = MediaWikiServices::getInstance()
			->getDBLoadBalancerFactory()
			->getPrimaryDatabase( 'virtual-oathauth' );

		$maxId = $dbw->newSelectQueryBuilder()
			->select( 'MAX(id)' )
			->from( 'oathauth_users' )
			->caller( __METHOD__ )
			->fetchField();

		$typeIds = OATHAuthServices::getInstance()->getModuleRegistry()->getModuleIds();

		$updated = 0;

		for ( $min = 0; $min <= $maxId; $min += $this->getBatchSize() ) {
			$max = $min + $this->getBatchSize();
			$this->output( "Now processing rows with id between $min and $max... (updated $updated users so far)\n" );

			$res = $dbw->newSelectQueryBuilder()
				->select( [
					'id',
					'module',
					'data',
				] )
				->from( 'oathauth_users' )
				->leftJoin(
					'oathauth_devices',
					null,
					'oad_user = id'
				)
				->where( [
					$dbw->buildComparison( '>=', [ 'id' => $min ] ),
					$dbw->buildComparison( '<', [ 'id' => $max ] ),

					// Only select rows that haven't been migrated yet, so no matching
					// oathauth_devices row.
					'oad_id' => null,
				] )
				->caller( __METHOD__ )
				->fetchResultSet();

			$toAdd = [];

			foreach ( $res as $row ) {
				$decodedData = FormatJson::decode( $row->data, true );

				if ( isset( $decodedData['keys'] ) ) {
					$updated++;

					foreach ( $decodedData['keys'] as $keyData ) {
						$toAdd[] = [
							'oad_user' => (int)$row->id,
							'oad_type' => $typeIds[$row->module],
							'oad_data' => FormatJson::encode( $keyData ),
						];
					}
				}
			}

			if ( $toAdd ) {
				$dbw->newInsertQueryBuilder()
					->insertInto( 'oathauth_devices' )
					->rows( $toAdd )
					->caller( __METHOD__ )
					->execute();
			}

			$this->waitForReplication();
		}

		$this->output( "Done, updated data for $updated users.\n" );
		return true;
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return __CLASS__;
	}
}

$maintClass = UpdateForMultipleDevicesSupport::class;
require_once RUN_MAINTENANCE_IF_MAIN;
