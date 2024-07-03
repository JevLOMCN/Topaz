<?php

namespace MediaWiki\Extension\Notifications\Mapper;

use InvalidArgumentException;
use MediaWiki\Extension\Notifications\DbFactory;

/**
 * Abstract mapper for model
 */
abstract class AbstractMapper {

	/**
	 * Echo database factory
	 * @var DbFactory
	 */
	protected $dbFactory;

	/**
	 * Event listeners for method like insert/delete
	 * @var array[]
	 */
	protected $listeners;

	/**
	 * @param DbFactory|null $dbFactory
	 */
	public function __construct( DbFactory $dbFactory = null ) {
		$this->dbFactory = $dbFactory ?? DbFactory::newFromDefault();
	}

	/**
	 * Attach a listener
	 *
	 * @param string $method Method name
	 * @param string $key Identification of the callable
	 * @param callable $callable
	 */
	public function attachListener( $method, $key, $callable ) {
		if ( !method_exists( $this, $method ) ) {
			throw new InvalidArgumentException( $method . ' does not exist in ' . get_class( $this ) );
		}
		if ( !isset( $this->listeners[$method] ) ) {
			$this->listeners[$method] = [];
		}

		$this->listeners[$method][$key] = $callable;
	}

	/**
	 * Detach a listener
	 *
	 * @param string $method Method name
	 * @param string $key identification of the callable
	 */
	public function detachListener( $method, $key ) {
		if ( isset( $this->listeners[$method] ) ) {
			unset( $this->listeners[$method][$key] );
		}
	}

	/**
	 * Get the listener for a method
	 *
	 * @param string $method
	 * @return callable[]
	 */
	public function getMethodListeners( $method ) {
		if ( !method_exists( $this, $method ) ) {
			throw new InvalidArgumentException( $method . ' does not exist in ' . get_class( $this ) );
		}

		return $this->listeners[$method] ?? [];
	}

}
