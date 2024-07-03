<?php

namespace MediaWiki\Extension\AbuseFilter\Maintenance;

use ExternalStoreAccess;
use FormatJson;
use LoggedUpdateMaintenance;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesBlobStore;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use UnexpectedValueException;
use Wikimedia\Rdbms\IMaintainableDatabase;
use Wikimedia\Rdbms\IResultWrapper;

// @codeCoverageIgnoreStart
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";
// @codeCoverageIgnoreEnd

/**
 * Performs several tasks aiming to update the stored var dumps for filter hits.
 *
 * See T213006 for a list.
 *
 * @codeCoverageIgnore
 * This script used to be covered by a test, but it was removed: the script was single-use, so
 * no more testing is needed. OTOH, maintaining the test was too hard because we needed to create
 * with serialized classes, which quickly becomes unsustainable.
 */
class UpdateVarDumps extends LoggedUpdateMaintenance {
	/** @var IMaintainableDatabase A connection to replica */
	private $dbr;
	/** @var IMaintainableDatabase A connection to the primary database */
	private $dbw;
	/** @var bool Whether we're performing a dry run */
	private $dryRun = false;
	/** @var int Count of rows in the abuse_filter_log table */
	private $allRowsCount;
	/** @var bool Whether to print progress markers */
	private $progressMarkers;
	/** @var string|null */
	private $printOrphanedFile;
	/** @var int|null How many seconds to sleep after each batch. */
	private $sleep;
	/** @var KeywordsManager */
	private $keywordsManager;
	/** @var VariablesBlobStore */
	private $varBlobStore;

	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Update AbuseFilter var dumps - T213006' );
		$this->addOption( 'dry-run-verbose', 'Perform a verbose dry run' );
		$this->addOption( 'dry-run', 'Perform a dry run' );
		$this->addOption( 'progress-markers', 'Print progress markers every 10 batches' );
		$this->addOption(
			'print-orphaned-records-to',
			'Print ExternalStore urls of orphaned ExternalStore records (if any) ' .
			'to the given file. Can use stdout, but it\'s not recommended for big databases.',
			false,
			true
		);
		$this->addOption( 'sleep', 'Sleep this many seconds after each batch', false, true );
		$this->requireExtension( 'Abuse Filter' );
		$this->setBatchSize( 500 );
	}

	/**
	 * @inheritDoc
	 */
	public function getUpdateKey() {
		return 'UpdateVarDumps';
	}

	/**
	 * @inheritDoc
	 */
	public function doDBUpdates() {
		if ( $this->hasOption( 'dry-run-verbose' ) || $this->hasOption( 'dry-run' ) ) {
			// This way the script can be called with dry-run-verbose only and we can check for dry-run
			$this->dryRun = true;
		}
		$this->progressMarkers = $this->hasOption( 'progress-markers' );
		$this->printOrphanedFile = $this->getOption( 'print-orphaned-records-to' );
		$this->sleep = $this->getOption( 'sleep' );

		$this->keywordsManager = AbuseFilterServices::getKeywordsManager();
		$this->varBlobStore = AbuseFilterServices::getVariablesBlobStore();

		// Faulty rows aren't inserted anymore, hence we can query the replica and update the primary database.
		$this->dbr = $this->getDB( DB_REPLICA );
		$this->dbw = $this->getDB( DB_PRIMARY );

		// Control batching with the primary key to keep the queries performant and allow gaps
		$this->allRowsCount = (int)$this->dbr->selectField(
			'abuse_filter_log',
			'MAX(afl_id)',
			[],
			__METHOD__
		);

		if ( $this->allRowsCount === 0 ) {
			$this->output( "...the abuse_filter_log table is empty.\n" );
			return !$this->dryRun;
		}

		// Do the actual work. Note that several actions are superfluous (e.g. in fixMissingDumps
		// we use "stored-text" but then we replace it in updateAflVarDump), but that's because of SRP.

		// First, ensure that afl_var_dump isn't empty
		$this->fixMissingDumps();
		// Then, ensure that abuse_filter_log.afl_var_dump only contains "stored-text:xxxx"
		$this->moveToText();
		// Then update the storage format in the text table
		$this->updateText();
		// Finally, replace "stored-text:xxxx" with "tt:xxxx" for all rows
		$this->updateAflVarDump();

		return !$this->dryRun;
	}

	/**
	 * Handle empty afl_var_dump. gerrit/16527 fixed a bug which caused an extra abuse_filter_log
	 * row to be inserted without the var dump for a given action. If we find a row identical to
	 * the current one but with a valid dump, just delete the current one. Otherwise, store a
	 * very basic var dump for sanity.
	 * This handles point 7. of T213006.
	 */
	private function fixMissingDumps() {
		$this->output( "...Checking for missing dumps (1/4)\n" );
		$batchSize = $this->getBatchSize();

		$prevID = 0;
		$curID = $batchSize;
		$deleted = $rebuilt = 0;
		do {
			$this->maybePrintProgress( $prevID );
			$brokenRows = $this->dbr->select(
				'abuse_filter_log',
				'*',
				[
					'afl_var_dump' => '',
					"afl_id > $prevID",
					"afl_id <= $curID"
				],
				__METHOD__,
				[ 'ORDER BY' => 'afl_id ASC' ]
			);
			$prevID = $curID;
			$curID += $batchSize;

			$res = $this->doFixMissingDumps( $brokenRows );
			$deleted += $res['deleted'];
			$rebuilt += $res['rebuilt'];
			$this->waitForReplication();
			$this->maybeSleep();
		} while ( $prevID <= $this->allRowsCount );

		if ( $this->dryRun ) {
			$this->output(
				"...found $deleted rows with blank afl_var_dump to delete, and " .
				"$rebuilt rows to rebuild.\n"
			);
		} else {
			$this->output(
				"...deleted $deleted rows with blank afl_var_dump, and rebuilt " .
				"$rebuilt rows.\n"
			);
		}
	}

	/**
	 * @param IResultWrapper $brokenRows
	 * @return int[]
	 */
	private function doFixMissingDumps( IResultWrapper $brokenRows ) {
		$deleted = 0;
		foreach ( $brokenRows as $row ) {
			if ( $row->afl_var_dump === '' ) {
				$findRow = array_diff_key(
					get_object_vars( $row ),
					[ 'afl_var_dump' => true, 'afl_id' => true ]
				);
				// This is the case where we may have a duplicate row. The wrong insertion happened
				// right before the correct one, so their afl_id should only differ by 1, but let's
				// play safe and only assume it's greater. Note that the two entries are guaranteed
				// to have the same timestamp.
				$findRow[] = 'afl_id > ' . $this->dbr->addQuotes( $row->afl_id );
				$saneDuplicate = $this->dbr->selectRow(
					'abuse_filter_log',
					'1',
					$findRow,
					__METHOD__
				);

				if ( $saneDuplicate ) {
					// Just delete the row!
					$deleted++;
					if ( !$this->dryRun ) {
						$this->dbw->delete(
							'abuse_filter_log',
							[ 'afl_id' => $row->afl_id ],
							__METHOD__
						);
					}
					continue;
				}
			}
			if ( $this->dryRun ) {
				continue;
			}
			// Build a VariableHolder with the only values we can be sure of
			$vars = VariableHolder::newFromArray( [
				'timestamp' => wfTimestamp( TS_UNIX, $row->afl_timestamp ),
				'action' => $row->afl_action
			] );
			// Add some action-specific variables
			if ( strpos( $row->afl_action, 'createaccount' ) !== false ) {
				$vars->setVar( 'accountname', $row->afl_user_text );
			} else {
				$vars->setVar( 'user_name', $row->afl_user_text );
				$title = Title::makeTitle( $row->afl_namespace, $row->afl_title );
				if ( $row->afl_action !== 'move' ) {
					$vars->setVar( 'page_title', $title->getText() );
					$vars->setVar( 'page_prefixedtitle', $title->getPrefixedText() );
				} else {
					$vars->setVar( 'moved_from_title', $title->getText() );
					$vars->setVar( 'moved_from_prefixedtitle', $title->getPrefixedText() );
				}
			}

			$storedID = $this->varBlobStore->storeVarDump( $vars );
			$this->dbw->update(
				'abuse_filter_log',
				[ 'afl_var_dump' => $storedID ],
				[ 'afl_id' => $row->afl_id ],
				__METHOD__
			);
		}
		$rebuilt = $brokenRows->numRows() - $deleted;
		return [ 'rebuilt' => $rebuilt, 'deleted' => $deleted ];
	}

	/**
	 * If afl_var_dump contains serialized data, move the dump to the text table.
	 * This handles point 1. of T213006.
	 */
	private function moveToText() {
		$this->output( "...Moving serialized data away from the abuse_filter_log table (2/4).\n" );
		$batchSize = $this->getBatchSize();

		$prevID = 0;
		$curID = $batchSize;
		$changeRows = $truncatedDumps = 0;
		do {
			$this->maybePrintProgress( $prevID );
			$res = $this->dbr->select(
				'abuse_filter_log',
				[ 'afl_id', 'afl_var_dump' ],
				[
					'afl_var_dump NOT ' . $this->dbr->buildLike(
						'stored-text:',
						$this->dbr->anyString()
					),
					'afl_var_dump NOT ' . $this->dbr->buildLike(
						'tt:',
						$this->dbr->anyString()
					),
					"afl_id > $prevID",
					"afl_id <= $curID"
				],
				__METHOD__,
				[ 'ORDER BY' => 'afl_id ASC' ]
			);

			$prevID = $curID;
			$curID += $batchSize;

			$result = $this->doMoveToText( $res );
			$changeRows += $result['change'];
			$truncatedDumps += $result['truncated'];
			$this->waitForReplication();
			$this->maybeSleep();
		} while ( $prevID <= $this->allRowsCount );

		$msg = $this->dryRun ?
			"...found $changeRows abuse_filter_log rows with serialized data and $truncatedDumps " .
				"truncated dumps to rebuild.\n" :
			"...moved $changeRows abuse_filter_log rows and rebuilt $truncatedDumps " .
				"truncated dumps.\n";

		$this->output( $msg );
	}

	/**
	 * @param IResultWrapper $rows
	 * @return int[]
	 */
	private function doMoveToText( IResultWrapper $rows ) {
		$changeRows = $truncatedDumps = 0;
		foreach ( $rows as $row ) {
			// Sanity: perform a very raw check to confirm that the dump is indeed a serialized value
			$re = '/^(a:\d+:{|O:25:"[Aa]buse[Ff]ilter[Vv]ariable[Hh]older":\d+:{)/';
			if ( !preg_match( $re, $row->afl_var_dump ) ) {
				$this->fatalError(
					"...found a value in afl_var_dump for afl_id {$row->afl_id} which is " .
					"neither a reference to the text table or a serialized value: {$row->afl_var_dump}.\n"
				);
			}

			// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			$stored = @unserialize( $row->afl_var_dump );
			if ( !$stored ) {
				$re = '/^O:25:"[Aa]buse[Ff]ilter[Vv]ariable[Hh]older":\d+:{/';
				if ( preg_match( $re, $row->afl_var_dump ) ) {
					$this->fatalError(
						"...found a corrupted afl_var_dump for afl_id {$row->afl_id} containing " .
						"a truncated object: {$row->afl_var_dump}.\n"
					);
				}
				$stored = $this->restoreTruncatedDump( $row->afl_var_dump );
				$truncatedDumps++;
			}
			if ( !is_array( $stored ) && !( $stored instanceof VariableHolder ) ) {
				$this->fatalError(
					'...found unexpected data type ( ' . gettype( $stored ) . ' ) in ' .
					"afl_var_dump for afl_id {$row->afl_id}.\n"
				);
			}
			$changeRows++;

			if ( !$this->dryRun ) {
				$holder = is_array( $stored ) ? VariableHolder::newFromArray( $stored ) : $stored;
				// Note: this will upgrade to the new JSON format, so we use tt:
				$newDump = $this->varBlobStore->storeVarDump( $holder );
				$this->dbw->update(
					'abuse_filter_log',
					[ 'afl_var_dump' => $newDump ],
					[ 'afl_id' => $row->afl_id ],
					__METHOD__
				);
			}
		}
		return [ 'change' => $changeRows, 'truncated' => $truncatedDumps ];
	}

	/**
	 * Try to restore a truncated dumps. This could happen for very old rows, where afl_var_dump
	 * was a blob instead of a longblob, and we tried to insert very long strings there.
	 * This handles point 9. of T214193.
	 *
	 * @param string $dump The broken serialized dump
	 * @return array With everything that we can restore from $dump on success
	 */
	private function restoreTruncatedDump( $dump ) {
		// This method makes various assumptions:
		// 1 - Everything is wrapped inside an array
		// 2 - Array elements can only be strings, integers, bools or null
		// 3 - Array keys can only be strings
		// As this is what a serialized dump should look like.
		$string = preg_replace( '/^a:\d+:{/', '', $dump );

		$ret = [];
		$key = null;

		while ( strlen( $string ) > 2 || $string === 'N;' ) {
			$type = substr( $string, 0, 2 );
			switch ( $type ) {
				case 's:':
					// Quotes aren't escaped, so we need to figure out how many characters to include
					$matches = [];
					if ( !preg_match( '/^s:(\d+):"/', $string, $matches ) ) {
						break 2;
					}
					$len = (int)$matches[1];
					$val = substr( $string, strlen( $matches[0] ), $len );
					if ( strlen( $val ) === $len ) {
						if ( $key === null ) {
							// It's an array key
							$key = $val;
						} else {
							$ret[$key] = $val;
							$key = null;
						}
						$offset = strlen( $matches[0] ) + $len + 2;
						break;
					} else {
						// The truncation happened in the middle of the string
						break 2;
					}
				case 'i:':
					if ( preg_match( '/^i:(-?\d+);/', $string, $matches ) ) {
						if ( $key === null ) {
							throw new UnexpectedValueException( "Unexpected integer key: $string" );
						}
						$ret[$key] = intval( $matches[1] );
						$key = null;
						$offset = strlen( $matches[0] );
						break;
					} else {
						break 2;
					}
				case 'b:':
					if ( preg_match( '/^b:([01]);/', $string, $matches ) ) {
						if ( $key === null ) {
							throw new UnexpectedValueException( "Unexpected bool key: $string" );
						}
						$ret[$key] = (bool)$matches[1];
						$key = null;
						$offset = 4;
						break;
					} else {
						break 2;
					}
				case 'N;':
					if ( $key === null ) {
						throw new UnexpectedValueException( "Unexpected null key: $string" );
					}
					$ret[$key] = null;
					$key = null;
					$offset = 2;
					break;
				default:
					break 2;
			}

			// Remove the value we have just parsed
			// @phan-suppress-next-next-line PhanPossiblyUndeclaredVariable
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullableInternal
			$string = substr( $string, $offset );
		}

		if ( $this->hasOption( 'dry-run-verbose' ) ) {
			$this->output(
				"...converted the following corrupted dump:\n\n$dump\n\n to this:\n\n" .
				var_export( $ret, true ) . "\n\n"
			);
		}

		return $ret;
	}

	/**
	 * If the text table (or the External Storage) contains a serialized variable holder
	 * or array, re-store it as a JSON-encoded array. This assumes that afl_var_dump rows starting
	 * with 'tt:' already point to JSON dumps, and afl_var_dump rows starting with 'stored-text:'
	 * only point to serialized dumps.
	 * This handles point 2. and 6. of T213006.
	 */
	private function updateText() {
		$this->output(
			"...Re-storing serialized dumps as JSON-encoded arrays for all rows (3/4).\n"
		);
		if ( $this->printOrphanedFile !== null && !$this->dryRun ) {
			$this->output( "Printing orphaned records to $this->printOrphanedFile.\n" );
			file_put_contents(
				$this->printOrphanedFile,
				"Records orphaned by AbuseFilter's updateVarDumps sccript\n",
				FILE_APPEND
			);
		}

		$batchSize = $this->getBatchSize();
		$prevID = 0;
		$curID = $batchSize;
		$count = 0;

		$idSQL = $this->dbr->buildIntegerCast( $this->dbr->strreplace(
			'afl_var_dump',
			$this->dbr->addQuotes( 'stored-text:' ),
			$this->dbr->addQuotes( '' )
		) );

		$dumpLike = $this->dbr->buildLike( 'stored-text:', $this->dbr->anyString() );
		$esAccess = MediaWikiServices::getInstance()->getExternalStoreAccess();
		do {
			$this->maybePrintProgress( $prevID );
			$res = $this->dbr->select(
				[ 'text', 'abuse_filter_log' ],
				[ 'old_id', 'old_text', 'old_flags' ],
				[
					"afl_var_dump $dumpLike",
					"afl_id > $prevID",
					"afl_id <= $curID"
				],
				__METHOD__,
				[ 'DISTINCT', 'ORDER BY' => 'old_id ASC' ],
				[ 'abuse_filter_log' => [ 'JOIN', "old_id = $idSQL" ] ]
			);

			$prevID = $curID;
			$curID += $batchSize;
			$count += $res->numRows();

			if ( !$this->dryRun ) {
				$this->doUpdateText( $res, $esAccess );
				$this->waitForReplication();
			}
			$this->maybeSleep();
		} while ( $prevID <= $this->allRowsCount );

		$msg = $this->dryRun
			? "...found $count text rows to update.\n"
			: "...updated $count text rows.\n";
		$this->output( $msg );
	}

	/**
	 * @param IResultWrapper $res text rows
	 * @param ExternalStoreAccess $esAccess
	 */
	private function doUpdateText( IResultWrapper $res, ExternalStoreAccess $esAccess ) {
		$orphaned = [];
		foreach ( $res as $row ) {
			// This is copied from the old AbuseFilter::loadVarDump
			$oldFlags = explode( ',', $row->old_flags );
			$text = $row->old_text;
			if ( in_array( 'external', $oldFlags ) ) {
				$text = $esAccess->fetchFromURL( $row->old_text );
			}
			if ( in_array( 'gzip', $oldFlags ) ) {
				$text = gzinflate( $text );
			}

			if ( FormatJson::decode( $text ) !== null ) {
				// Already in the new format, apparently.
				if (
					!in_array( 'utf-8', $oldFlags, true ) ||
					in_array( 'nativeDataArray', $oldFlags, true )
				) {
					// Sanity
					$this->fatalError( "Row {$row->old_id} is JSON-encoded with wrong flags: {$row->old_flags}" );
				}
				continue;
			}

			// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			$obj = @unserialize( $text );

			if ( !$obj ) {
				// Under certain conditions, there might be a truncated dump here, see T264513
				$obj = $this->restoreTruncatedDump( $text );
			}

			if ( $obj instanceof VariableHolder ) {
				$varManager = AbuseFilterServices::getVariablesManager();
				$varArray = $varManager->dumpAllVars( $obj, [ 'old_wikitext', 'new_wikitext' ] );
			} elseif ( is_array( $obj ) ) {
				$varArray = $obj;
			} else {
				$type = is_object( $obj ) ? get_class( $obj ) : gettype( $obj );
				throw new UnexpectedValueException( "Unexpected type for stored blob: $type" );
			}
			$varArray = $this->updateVariables( $varArray );
			// Recreating flags will also ensure that we don't add 'nativeDataArray'
			$newFlags = [ 'utf-8' ];
			// This is copied from the old AbuseFilter::storeVarDump
			$toStore = FormatJson::encode( $varArray );
			if ( in_array( 'gzip', $oldFlags ) && function_exists( 'gzdeflate' ) ) {
				$toStore = gzdeflate( $toStore );
				$newFlags[] = 'gzip';
			}
			if ( in_array( 'external', $oldFlags ) ) {
				$orphaned[] = $row->old_text;
				$toStore = $esAccess->insert( $toStore );
				$newFlags[] = 'external';
			}

			$this->dbw->update(
				'text',
				[
					'old_text' => $toStore,
					'old_flags' => implode( ',', $newFlags )
				],
				[ 'old_id' => $row->old_id ],
				__METHOD__
			);
		}
		if ( $this->printOrphanedFile !== null && $orphaned ) {
			file_put_contents( $this->printOrphanedFile, implode( ', ', $orphaned ) . "\n", FILE_APPEND );
		}
	}

	/**
	 * Given a stored object, removes some disabled variables and update deprecated ones.
	 * Also ensure that core variables are lowercase.
	 * Handles points 4., 5. and 8. of T213006.
	 *
	 * @param array $vars The stored vars.
	 * @return array
	 */
	private function updateVariables( array $vars ) {
		// Remove all variables used in the past to store metadata
		unset( $vars['context'], $vars['logged_local_ids'], $vars['logged_global_ids'] );

		$builtinVars = $this->getBuiltinVarNames();
		$newVars = [];
		foreach ( $vars as $oldName => $value ) {
			$lowerName = strtolower( $oldName );
			if ( $lowerName !== $oldName && array_key_exists( $lowerName, $builtinVars ) ) {
				$oldName = $lowerName;
			}
			$deprecatedVars = $this->keywordsManager->getDeprecatedVariables();
			$newName = $deprecatedVars[$oldName] ?? $oldName;
			$newVars[$newName] = $value;
		}
		return $newVars;
	}

	/**
	 * Get a set of builtin variable names. Copied from VariableHolder::dumpAllVars.
	 * @return array [ varname => true ] for instantaneous search. All names are lowercase
	 */
	private function getBuiltinVarNames() {
		global $wgRestrictionTypes;

		static $coreVariables = null;

		if ( $coreVariables ) {
			return $coreVariables;
		}

		$activeVariables = array_keys( $this->keywordsManager->getVarsMappings() );
		$deprecatedVariables = array_keys( $this->keywordsManager->getDeprecatedVariables() );
		$disabledVariables = array_keys( $this->keywordsManager->getDisabledVariables() );
		$coreVariables = array_merge( $activeVariables, $deprecatedVariables, $disabledVariables );

		$prefixes = [ 'moved_from', 'moved_to', 'page' ];
		foreach ( $wgRestrictionTypes as $action ) {
			foreach ( $prefixes as $prefix ) {
				$coreVariables[] = "{$prefix}_restrictions_$action";
			}
		}

		$coreVariables = array_fill_keys( $coreVariables, true );
		$coreVariables = array_change_key_case( $coreVariables );

		return $coreVariables;
	}

	/**
	 * Replace 'stored-text:' with 'tt:' in afl_var_dump. Handles point 3. of T213006.
	 */
	private function updateAflVarDump() {
		$this->output(
			"...Replacing the 'stored-text:' prefix with 'tt:' (4/4).\n"
		);

		$batchSize = $this->getBatchSize();

		// Use native SQL functions so that we can update all rows at the same time.
		$newIdSQL = $this->dbw->strreplace(
			'afl_var_dump',
			$this->dbr->addQuotes( 'stored-text:' ),
			$this->dbr->addQuotes( 'tt:' )
		);

		$prevID = 0;
		$curID = $batchSize;
		$numRows = 0;
		do {
			$this->maybePrintProgress( $prevID );
			$args = [
				'abuse_filter_log',
				[ "afl_var_dump = $newIdSQL" ],
				[
					"afl_id > $prevID",
					"afl_id <= $curID",
					'afl_var_dump ' . $this->dbr->buildLike( 'stored-text:', $this->dbr->anyString() )
				],
				__METHOD__,
				[ 'ORDER BY' => 'afl_id ASC' ]
			];
			if ( $this->dryRun ) {
				$numRows += $this->dbr->selectRowCount( ...$args );
			} else {
				$this->dbw->update( ...$args );
				$numRows += $this->dbw->affectedRows();
				$this->waitForReplication();
			}

			$prevID = $curID;
			$curID += $batchSize;
			$this->maybeSleep();
		} while ( $prevID <= $this->allRowsCount );

		if ( $this->dryRun ) {
			$this->output( "...would change afl_var_dump for $numRows rows.\n" );
		} else {
			$this->output( "...updated afl_var_dump prefix for $numRows rows.\n" );
		}
	}

	/**
	 * Print a progress marker if the respective option is enabled
	 *
	 * @param int $start
	 */
	private function maybePrintProgress( int $start ): void {
		if ( $this->progressMarkers && $start % ( 10 * $this->getBatchSize() ) === 0 ) {
			$end = $start + $this->getBatchSize();
			$this->output( "...Doing range $start - $end\n" );
		}
	}

	/**
	 * Sleep for a while, if required. Note: checking the value is several
	 * orders of magnitude faster than calling sleep(0).
	 */
	private function maybeSleep(): void {
		if ( $this->sleep ) {
			sleep( $this->sleep );
		}
	}
}

$maintClass = UpdateVarDumps::class;
require_once RUN_MAINTENANCE_IF_MAIN;
