<?php

namespace MediaWiki\Extension\AbuseFilter;

use Wikimedia\Diff\Diff;
use Wikimedia\Diff\TableDiffFormatter;

/**
 * Like TableDiffFormatter, but will always render the full context (even for empty diffs).
 *
 * @todo Consider moving to MW core (as a separate class, or as an option to TableDiffFormatter)
 *
 * @internal
 */
class TableDiffFormatterFullContext extends TableDiffFormatter {
	/**
	 * Format a diff.
	 *
	 * @param Diff $diff
	 * @return string The formatted output.
	 */
	public function format( $diff ) {
		$xlen = $ylen = 0;

		// Calculate the length of the left and the right side
		foreach ( $diff->edits as $edit ) {
			if ( $edit->orig ) {
				$xlen += count( $edit->orig );
			}
			if ( $edit->closing ) {
				$ylen += count( $edit->closing );
			}
		}

		// Just render the diff with no preprocessing
		$this->startDiff();
		$this->block( 1, $xlen, 1, $ylen, $diff->edits );
		return $this->endDiff();
	}
}
