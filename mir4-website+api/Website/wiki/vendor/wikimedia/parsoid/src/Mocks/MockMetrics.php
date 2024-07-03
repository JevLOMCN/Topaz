<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Mocks;

use Liuggio\StatsdClient\Entity\StatsdDataInterface;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;

class MockMetrics implements StatsdDataFactoryInterface {

	/** @var array */
	public $log;

	/** @inheritDoc */
	public function timing( $key, $time ) {
		$this->log[] = [ 'timing', $key, $time ];
	}

	/** @inheritDoc */
	public function gauge( $key, $value ) {
		$this->log[] = [ 'gauge', $key, $value ];
	}

	/** @inheritDoc */
	public function set( $key, $value ) {
		$this->log[] = [ 'set', $key, $value ];
		return [];
	}

	/** @inheritDoc */
	public function increment( $key ) {
		$this->log[] = [ 'increment', $key ];
		return [];
	}

	/** @inheritDoc */
	public function decrement( $key ) {
		$this->log[] = [ 'decrement', $key ];
		return [];
	}

	/** @inheritDoc */
	public function updateCount( $key, $delta ) {
		$this->log[] = [ 'updateCount', $key, $delta ];
		return [];
	}

	/** @inheritDoc */
	public function produceStatsdData(
		$key, $value = 1, $metric = StatsdDataInterface::STATSD_METRIC_COUNT
	) {
		// @phan-suppress-next-line PhanTypeMismatchReturn FIXME, phan seems right
		return $metric;
	}

}
