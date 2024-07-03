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
 * @author Ori Livneh <ori@wikimedia.org>
 */

namespace Wikimedia;

use Serializable;

/**
 * Represents a running, online estimate of a p-quantile for a series
 * of observations using the P-squared algorithm.
 *
 * The algorithm is from "The P-Square Algorithm for Dynamic Calculation of
 * Percentiles and Histograms without Storing Observations," Communications of
 * the ACM, October 1985 by R. Jain and I. Chlamtac.
 */
class PSquare implements Serializable {
	/**
	 * Percentile to estimate.
	 * @var float $p
	 */
	private $p;

	/**
	 * Position of each marker.
	 * @var int[] $positions
	 */
	private $positions;

	/**
	 * Desired position of each marker.
	 * @var float[] $desired
	 */
	private $desired;

	/** @var float[] $increments */
	private $increments;

	/**
	 * Number of observations.
	 * @var int $numObservations
	 */
	private $numObservations = 0;

	/**
	 * Height of each marker.
	 * @var float[] $heights
	 */
	private $heights = [];

	/**
	 * @param float $p the percentile (defaults to 0.5, or median).
	 */
	public function __construct( $p = 0.5 ) {
		$this->p = $p;
		$this->positions = [ 0, 1, 2, 3, 4 ];
		$this->desired = [ 0, ( 2 * $p ), ( 4 * $p ), 2 + ( 2 * $p ), 4 ];
		$this->increments = [ 0, ( $p / 2 ), $p, ( ( 1 + $p ) / 2 ), 1 ];
	}

	/**
	 * Export state, e.g. for serializing and caching.
	 *
	 * @return array
	 */
	public function __serialize(): array {
		return [
			'percentile' => $this->p,
			'positions' => $this->positions,
			'desired' => $this->desired,
			'increments' => $this->increments,
			'numObservations' => $this->numObservations,
			'heights' => $this->heights,
		];
	}

	/**
	 * @param array $data
	 */
	public function __unserialize( array $data ): void {
		$this->p = $data['percentile'];
		$this->positions = $data['positions'];
		$this->desired = $data['desired'];
		$this->increments = $data['increments'];
		$this->numObservations = $data['numObservations'];
		$this->heights = $data['heights'];
	}

	public function serialize() {
		// TODO: Remove once PHP 7.4+ is required
		return serialize( $this->__serialize() );
	}

	/**
	 * @param string $serialized
	 */
	public function unserialize( $serialized ): void {
		// TODO: Remove once PHP 7.4+ is required
		$this->__unserialize( unserialize( $serialized ) );
	}

	/**
	 * Get the total number of accumulated observations.
	 *
	 * @return int
	 */
	public function getCount() {
		return $this->numObservations;
	}

	/**
	 * Add an observation.
	 *
	 * @param int|float $x Value to add
	 */
	public function addObservation( $x ) {
		$this->numObservations++;

		if ( $this->numObservations <= 5 ) {
			$this->heights[] = $x;
			if ( $this->numObservations === 5 ) {
				sort( $this->heights );
			}
			return;
		}

		if ( $x < $this->heights[0] ) {
			$this->heights[0] = $x;
			$k = 0;
		} elseif ( $x >= $this->heights[4] ) {
			$this->heights[4] = $x;
			$k = 3;
		} else {
			for ( $i = 1; $i < 5; $i++ ) {
				if ( $x < $this->heights[$i] ) {
					$k = $i - 1;
					break;
				}
			}
		}

		// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
		for ( $i = $k + 1; $i < 5; $i++ ) {
			$this->positions[$i]++;
		}

		for ( $i = 0; $i < 5; $i++ ) {
			$this->desired[$i] += $this->increments[$i];
		}

		for ( $i = 1; $i < 4; $i++ ) {
			$n     = $this->positions[$i];
			$nPrev = $this->positions[$i - 1];
			$nNext = $this->positions[$i + 1];

			$d = $this->desired[$i] - $n;

			if ( ( $d >= 1 && $nNext - $n > 1 ) || ( $d <= -1 && $nPrev - $n < -1 ) ) {
				$d = ( $d < 0 ) ? -1 : 1;

				$q     = $this->computeParabolic( $i, $d );
				$qPrev = $this->heights[$i - 1];
				$qNext = $this->heights[$i + 1];

				if ( $qPrev < $q && $q < $qNext ) {
					$this->heights[$i] = $q;
				} else {
					$this->heights[$i] = $this->computeLinear( $i, $d );
				}

				$this->positions[$i] += $d;
			}
		}
	}

	/**
	 * Use piecewise parabolic prediction to predict the ideal
	 * height of a marker.
	 *
	 * @param int $i index of marker to adjust
	 * @param int $d always -1 or 1
	 * @return float ideal height of marker
	 */
	private function computeParabolic( $i, $d ) {
		$q     = $this->heights[$i];
		$qPrev = $this->heights[$i - 1];
		$qNext = $this->heights[$i + 1];

		$n     = $this->positions[$i];
		$nPrev = $this->positions[$i - 1];
		$nNext = $this->positions[$i + 1];

		return ( $q +
			$d / ( $nNext - $nPrev ) *
			(
				( $n - $nPrev + $d ) * ( $qNext - $q ) / ( $nNext - $n ) +
				( $nNext - $n - $d ) * ( $q - $qPrev ) / ( $n - $nPrev )
			)
		);
	}

	/**
	 * Linear formula to predict ideal position of a marker.
	 *
	 * @param int $i index of marker to adjust
	 * @param int $d always -1 or 1
	 * @return float ideal height of marker
	 */
	private function computeLinear( $i, $d ) {
		$q = $this->heights[$i];
		$n = $this->positions[$i];
		return ( $q + $d *
			( $this->heights[$i + $d] - $q ) /
			( $this->positions[$i + $d] - $n )
		);
	}

	/**
	 * Get the estimated p-quantile value.
	 *
	 * @return float
	 */
	public function getValue() {
		// If we have five samples or fewer, fall back to a naive method.
		if ( $this->getCount() <= 5 ) {
			sort( $this->heights );
			$i = $this->p * count( $this->heights );
			if ( $i === floor( $i ) ) {
				return ( $this->heights[$i - 1] + $this->heights[$i] ) / 2;
			} else {
				return $this->heights[floor( $i )];
			}
		}

		return $this->heights[2];
	}
}
