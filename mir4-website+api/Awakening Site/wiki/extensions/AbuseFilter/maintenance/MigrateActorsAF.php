<?php
/**
 * Migrate actors to the 'actor' table
 *
 * @file
 * @ingroup Maintenance
 */

namespace MediaWiki\Extension\AbuseFilter\Maintenance;

use LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use stdClass;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

/**
 * Maintenance script that migrates actors from AbuseFilter tables to the 'actor' table.
 *
 * Code was copy-pasted from core's maintenance/includes/MigrateActors.php (before removal
 * in ba3155214), except our custom ::doDBUpdates.
 *
 * @ingroup Maintenance
 */
class MigrateActorsAF extends LoggedUpdateMaintenance {

	/** @var string[]|null */
	private $tables = null;

	public function __construct() {
		parent::__construct();
		$this->addOption( 'tables', 'List of tables to process, comma-separated', false, true );
		$this->setBatchSize( 100 );
		$this->addDescription( 'Migrates actors from AbuseFilter tables to the \'actor\' table' );
		$this->requireExtension( 'Abuse Filter' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return __CLASS__;
	}

	/**
	 * @inheritDoc
	 */
	protected function doDBUpdates() {
		$tables = $this->getOption( 'tables' );
		if ( $tables !== null ) {
			$this->tables = explode( ',', $tables );
		}

		$stage = $this->getConfig()->get( 'AbuseFilterActorTableSchemaMigrationStage' );
		if ( !( $stage & SCHEMA_COMPAT_WRITE_NEW ) ) {
			$this->output(
				'...cannot update while $wgAbuseFilterActorTableSchemaMigrationStage ' .
				"lacks SCHEMA_COMPAT_WRITE_NEW\n"
			);
			return false;
		}

		$errors = 0;
		$errors += $this->migrate( 'abuse_filter', 'af_id', 'af_user', 'af_user_text', 'af_actor' );
		$errors += $this->migrate(
			'abuse_filter_history', 'afh_id', 'afh_user', 'afh_user_text', 'afh_actor' );

		return $errors === 0;
	}

	/**
	 * @param string $table
	 * @return bool
	 */
	private function doTable( $table ) {
		return $this->tables === null || in_array( $table, $this->tables, true );
	}

	/**
	 * Calculate a "next" condition and a display string
	 * @param IDatabase $dbw
	 * @param string[] $primaryKey Primary key of the table.
	 * @param stdClass $row Database row
	 * @return array [ string $next, string $display ]
	 */
	private function makeNextCond( $dbw, $primaryKey, $row ) {
		$next = '';
		$display = [];
		for ( $i = count( $primaryKey ) - 1; $i >= 0; $i-- ) {
			$field = $primaryKey[$i];
			$display[] = $field . '=' . $row->$field;
			$value = $dbw->addQuotes( $row->$field );
			if ( $next === '' ) {
				$next = "$field > $value";
			} else {
				$next = "$field > $value OR $field = $value AND ($next)";
			}
		}
		$display = implode( ' ', array_reverse( $display ) );
		return [ $next, $display ];
	}

	/**
	 * Make the subqueries for `actor_id`
	 * @param ISQLPlatform $dbw
	 * @param string $userField User ID field name
	 * @param string $nameField User name field name
	 * @return string SQL fragment
	 */
	private function makeActorIdSubquery( ISQLPlatform $dbw, $userField, $nameField ) {
		$idSubquery = $dbw->buildSelectSubquery(
			'actor',
			'actor_id',
			[ "$userField = actor_user" ],
			__METHOD__
		);
		$nameSubquery = $dbw->buildSelectSubquery(
			'actor',
			'actor_id',
			[ "$nameField = actor_name" ],
			__METHOD__
		);
		return "CASE WHEN $userField = 0 OR $userField IS NULL THEN $nameSubquery ELSE $idSubquery END";
	}

	/**
	 * Add actors for anons in a set of rows
	 *
	 * @param IDatabase $dbw
	 * @param string $nameField
	 * @param stdClass[] &$rows
	 * @param array &$complainedAboutUsers
	 * @param int &$countErrors
	 * @return int Count of actors inserted
	 */
	private function addActorsForRows(
		IDatabase $dbw, $nameField, array &$rows, array &$complainedAboutUsers, &$countErrors
	) {
		$needActors = [];
		$countActors = 0;
		$userNameUtils = MediaWikiServices::getInstance()->getUserNameUtils();

		$keep = [];
		foreach ( $rows as $index => $row ) {
			$keep[$index] = true;
			if ( $row->actor_id === null ) {
				$name = $row->$nameField;
				if ( $userNameUtils->isUsable( $name ) ) {
					if ( !isset( $complainedAboutUsers[$name] ) ) {
						$complainedAboutUsers[$name] = true;
						$this->error(
							"User name \"$name\" is usable, cannot create an anonymous actor for it."
							. " Your database has likely been corrupted, and may require manual intervention.\n"
						);
					}
					unset( $keep[$index] );
					$countErrors++;
				} else {
					$needActors[$name] = 0;
				}
			}
		}
		$rows = array_intersect_key( $rows, $keep );

		if ( $needActors ) {
			$dbw->insert(
				'actor',
				array_map( static function ( $v ) {
					return [
						'actor_name' => $v,
					];
				}, array_keys( $needActors ) ),
				__METHOD__,
				[ 'IGNORE' ]
			);
			$countActors += $dbw->affectedRows();

			$res = $dbw->select(
				'actor',
				[ 'actor_id', 'actor_name' ],
				[ 'actor_name' => array_map( 'strval', array_keys( $needActors ) ) ],
				__METHOD__
			);
			foreach ( $res as $row ) {
				$needActors[$row->actor_name] = $row->actor_id;
			}
			foreach ( $rows as $row ) {
				if ( $row->actor_id === null ) {
					$row->actor_id = $needActors[$row->$nameField];
				}
			}
		}

		return $countActors;
	}

	/**
	 * Migrate actors in a table.
	 *
	 * Assumes any row with the actor field non-zero have already been migrated.
	 * Blanks the name field when migrating.
	 *
	 * @param string $table Table to migrate
	 * @param string|string[] $primaryKey Primary key of the table.
	 * @param string $userField User ID field name
	 * @param string $nameField User name field name
	 * @param string $actorField Actor field name
	 * @return int Number of errors
	 */
	private function migrate( $table, $primaryKey, $userField, $nameField, $actorField ) {
		if ( !$this->doTable( $table ) ) {
			$this->output( "Skipping $table, not included in --tables\n" );
			return 0;
		}

		$dbw = $this->getDB( DB_PRIMARY );
		if ( !$dbw->fieldExists( $table, $userField, __METHOD__ ) ) {
			$this->output( "No need to migrate $table.$userField, field does not exist\n" );
			return 0;
		}

		$complainedAboutUsers = [];

		$primaryKey = (array)$primaryKey;
		$pkFilter = array_fill_keys( $primaryKey, true );
		$this->output(
			"Beginning migration of $table.$userField and $table.$nameField to $table.$actorField\n"
		);
		$this->waitForReplication();

		$actorIdSubquery = $this->makeActorIdSubquery( $dbw, $userField, $nameField );
		$next = '1=1';
		$countUpdated = 0;
		$countActors = 0;
		$countErrors = 0;
		while ( true ) {
			// Fetch the rows needing update
			$res = $dbw->select(
				$table,
				array_merge( $primaryKey, [ $userField, $nameField, 'actor_id' => $actorIdSubquery ] ),
				[
					$actorField => 0,
					$next,
				],
				__METHOD__,
				[
					'ORDER BY' => $primaryKey,
					'LIMIT' => $this->mBatchSize,
				]
			);
			if ( !$res->numRows() ) {
				break;
			}

			// Insert new actors for rows that need one
			$rows = iterator_to_array( $res );
			$lastRow = end( $rows );
			$countActors += $this->addActorsForRows(
				$dbw, $nameField, $rows, $complainedAboutUsers, $countErrors
			);

			// Update the existing rows
			foreach ( $rows as $row ) {
				if ( !$row->actor_id ) {
					[ , $display ] = $this->makeNextCond( $dbw, $primaryKey, $row );
					$this->error(
						"Could not make actor for row with $display "
						. "$userField={$row->$userField} $nameField={$row->$nameField}\n"
					);
					$countErrors++;
					continue;
				}
				$dbw->update(
					$table,
					[
						$actorField => $row->actor_id,
					],
					array_intersect_key( (array)$row, $pkFilter ) + [
						$actorField => 0
					],
					__METHOD__
				);
				$countUpdated += $dbw->affectedRows();
			}

			[ $next, $display ] = $this->makeNextCond( $dbw, $primaryKey, $lastRow );
			$this->output( "... $display\n" );
			$this->waitForReplication();
		}

		$this->output(
			"Completed migration, updated $countUpdated row(s) with $countActors new actor(s), "
			. "$countErrors error(s)\n"
		);
		return $countErrors;
	}

}

$maintClass = MigrateActorsAF::class;
require_once RUN_MAINTENANCE_IF_MAIN;
