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

/**
 * Represents a single set of changes all effecting neighboring lines
 */
class DiffGroup {
	/**
	 * @var int[] The left and right position this change starts at
	 */
	protected $position;

	/**
	 * @var string[] The lines that have been added
	 */
	protected $new = [];

	/**
	 * @var string[] The lines that have been removed
	 */
	protected $old = [];

	/**
	 * @param int $leftPos The starting line number in the left text
	 * @param int $rightPos The starting line number in the right text
	 */
	public function __construct( $leftPos, $rightPos ) {
		// +1 due to the original code use 1 indexing for this result
		$this->position = [
			'right-pos' => $rightPos + 1,
			'left-pos' => $leftPos + 1,
		];
	}

	/**
	 * @param string $line Line in the right text but not in the left
	 */
	public function add( $line ) {
		$this->new[] = $line;
	}

	/**
	 * @param string $line Line in the left text but not in the right
	 */
	public function subtract( $line ) {
		$this->old[] = $line;
	}

	/**
	 * @return array[] set of changes
	 * Each change consists of:
	 * An 'action', one of:
	 *   - add
	 *   - subtract
	 *   - change
	 * 'content' that was added or removed, or in the case
	 *    of a change, 'old_content' and 'new_content'
	 * 'left_pos' and 'right_pos' (in 1-indexed lines) of the change.
	 */
	public function getChangeSet() {
		$old = implode( "\n", $this->old );
		$new = implode( "\n", $this->new );
		$position = $this->position;
		$changeSet = [];

		// The implodes must come first because we consider [ '' ] to also be false
		// meaning a blank link replaced with content is an addition
		if ( $old && $new ) {
			$min = min( count( $this->old ), count( $this->new ) );
			$changeSet[] = $position + [
				'action' => 'change',
				'old_content' => implode( "\n", array_slice( $this->old, 0, $min ) ),
				'new_content' => implode( "\n", array_slice( $this->new, 0, $min ) ),
			];
			$position['left-pos'] += $min;
			$position['right-pos'] += $min;
			$old = implode( "\n", array_slice( $this->old, $min ) );
			$new = implode( "\n", array_slice( $this->new, $min ) );
		}

		if ( $new ) {
			$changeSet[] = $position + [
				'action' => 'add',
				'content' => $new,
			];
		} elseif ( $old ) {
			$changeSet[] = $position + [
				'action' => 'subtract',
				'content' => $old,
			];
		}

		return $changeSet;
	}
}
