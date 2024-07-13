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

class MathGenerateSql extends Maintenance {
	private const AVAILABLE_DIALECTS = [ 'mysql', 'postgres', 'sqlite' ];

	public function execute() {
		$sqlPath = dirname( __DIR__ ) . "/sql";
		$tables = $sqlPath . "/*.json";
		$this->output( "Looking for Doctrine DBAL definitions at '$tables'.\n" );
		foreach ( glob( $tables ) as $file ) {
			$table = basename( $file, ".json" );
			$this->output( "Processing '$table'.\n" );
			foreach ( self::AVAILABLE_DIALECTS as $dialect ) {
				$target = "$sqlPath/$dialect/$table.sql";
				$this->output( "Writing '$target'.\n" );
				$this->loadWithArgv( [ "--json=$file", "--type=$dialect", "--sql=$target" ] );
				$child = $this->runChild( 'GenerateSchemaSql' );
				$child->execute();
			}
		}
		$this->output( "done.\n" );
	}
}

$maintClass = MathGenerateSql::class;
require_once RUN_MAINTENANCE_IF_MAIN;
