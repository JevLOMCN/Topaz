<?php

namespace MediaWiki\Extension\Notifications;

/**
 * MediaWiki Extension: Echo
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * This program is distributed WITHOUT ANY WARRANTY.
 */

/**
 * @file
 * @ingroup Extensions
 * @author Erik Bernhardson
 */

use UnexpectedValueException;
use Wikimedia\Diff\Diff;
use Wikimedia\Diff\UnifiedDiffFormatter;

/**
 * Calculates the individual sets of differences between two pieces of text
 * as individual groupings of add, subtract, and change actions. Internally
 * uses 0-indexing for positions.  All results from the class are 1 indexed
 * to stay consistent with the original diff output and the previous diff
 * parsing code.
 */
class DiffParser {

	/**
	 * @var int The number of characters the diff prefixes a line with
	 */
	protected $prefixLength = 1;

	/**
	 * @var string[] The text of the left side of the diff operation
	 */
	protected $left;

	/**
	 * @var int The current position within the left text
	 */
	protected $leftPos;

	/**
	 * @var string[] The text of the right side of the diff operation
	 */
	protected $right;

	/**
	 * @var int The current position within the right text
	 */
	protected $rightPos;

	/**
	 * @var array[] Set of add, subtract, or change operations within the diff
	 */
	protected $changeSet;

	/**
	 * Get the set of add, subtract, and change operations required to transform leftText into rightText
	 *
	 * @param string $leftText The left, or old, revision of the text
	 * @param string $rightText The right, or new, revision of the text
	 * @return array[] Array of arrays containing changes to individual groups of lines within the text
	 * Each change consists of:
	 * An 'action', one of:
	 * - add
	 * - subtract
	 * - change
	 * 'content' that was added or removed, or in the case
	 *     of a change, 'old_content' and 'new_content'
	 * 'left_pos' and 'right_pos' (in 1-indexed lines) of the change.
	 */
	public function getChangeSet( $leftText, $rightText ) {
		$left = trim( $leftText );
		$right = trim( $rightText );

		if ( $left === '' ) {
			// fixes T155998
			return $this->getChangeSetFromEmptyLeft( $right );
		}

		$diffs = new Diff( explode( "\n", $left ), explode( "\n", $right ) );
		$format = new UnifiedDiffFormatter();
		$diff = $format->format( $diffs );

		return $this->parse( $diff, $left, $right );
	}

	/**
	 * If we add content to an empty page the changeSet can be composed straightaway
	 *
	 * @param string $right
	 * @return array[] See {@see getChangeSet}
	 */
	private function getChangeSetFromEmptyLeft( $right ) {
		$rightLines = explode( "\n", $right );

		return [
			'_info' => [
				'lhs-length' => 1,
				'rhs-length' => count( $rightLines ),
				'lhs' => [ '' ],
				'rhs' => $rightLines
			],
			[
				'right-pos' => 1,
				'left-pos' => 1,
				'action' => 'add',
				'content' => $right,
			]
		];
	}

	/**
	 * Duplicates the check from the global wfDiff function to determine
	 * if we are using internal or external diff utilities
	 *
	 * @deprecated since 1.29, the internal diff parser is always used
	 * @return bool
	 */
	protected static function usingInternalDiff() {
		return true;
	}

	/**
	 * Parse the unified diff output into an array of changes to individual groups of the text
	 *
	 * @param string $diff The unified diff output
	 * @param string $left The left side of the diff used for sanity checks
	 * @param string $right The right side of the diff used for sanity checks
	 *
	 * @return array[]
	 */
	protected function parse( $diff, $left, $right ) {
		$this->left = explode( "\n", $left );
		$this->right = explode( "\n", $right );
		$diff = explode( "\n", $diff );

		$this->leftPos = 0;
		$this->rightPos = 0;
		$this->changeSet = [
			'_info' => [
				'lhs-length' => count( $this->left ),
				'rhs-length' => count( $this->right ),
				'lhs' => $this->left,
				'rhs' => $this->right,
			],
		];

		$change = null;
		foreach ( $diff as $line ) {
			$change = $this->parseLine( $line, $change );
		}
		if ( $change === null ) {
			return $this->changeSet;
		}

		return array_merge( $this->changeSet, $change->getChangeSet() );
	}

	/**
	 * Parse the next line of the unified diff output
	 *
	 * @param string $line The next line of the unified diff
	 * @param DiffGroup|null $change Changes the immediately previous lines
	 *
	 * @return DiffGroup|null Changes to this line and any changed lines immediately previous
	 */
	protected function parseLine( $line, DiffGroup $change = null ) {
		if ( $line ) {
			$op = $line[0];
			if ( strlen( $line ) > $this->prefixLength ) {
				$line = substr( $line, $this->prefixLength );
			} else {
				$line = '';
			}
		} else {
			$op = ' ';
		}

		switch ( $op ) {
			case '@':
				// metadata
				if ( $change !== null ) {
					$this->changeSet = array_merge( $this->changeSet, $change->getChangeSet() );
					$change = null;
				}
				// @@ -start,numLines +start,numLines @@
				[ , $left, $right ] = explode( ' ', $line, 3 );
				[ $this->leftPos ] = explode( ',', substr( $left, 1 ), 2 );
				[ $this->rightPos ] = explode( ',', substr( $right, 1 ), 2 );
				$this->leftPos = (int)$this->leftPos;
				$this->rightPos = (int)$this->rightPos;

				// -1 because diff is 1 indexed, and we are 0 indexed
				$this->leftPos--;
				$this->rightPos--;
				break;

			case ' ':
				// No changes
				if ( $change !== null ) {
					$this->changeSet = array_merge( $this->changeSet, $change->getChangeSet() );
					$change = null;
				}
				$this->leftPos++;
				$this->rightPos++;
				break;

			case '-':
				// subtract
				if ( $this->left[$this->leftPos] !== $line ) {
					throw new UnexpectedValueException( 'Positional error: left' );
				}
				if ( $change === null ) {
					// @phan-suppress-next-line PhanTypeMismatchArgument
					$change = new DiffGroup( $this->leftPos, $this->rightPos );
				}
				$change->subtract( $line );
				$this->leftPos++;
				break;

			case '+':
				// add
				if ( $this->right[$this->rightPos] !== $line ) {
					throw new UnexpectedValueException( 'Positional error: right' );
				}
				if ( $change === null ) {
					// @phan-suppress-next-line PhanTypeMismatchArgument
					$change = new DiffGroup( $this->leftPos, $this->rightPos );
				}
				$change->add( $line );
				$this->rightPos++;
				break;

			default:
				throw new UnexpectedValueException( 'Unknown Diff Operation: ' . $op );
		}

		return $change;
	}
}
