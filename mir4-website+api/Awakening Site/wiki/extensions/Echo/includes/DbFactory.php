<?php

namespace MediaWiki\Extension\Notifications;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Database factory class, this will determine whether to use the main database
 * or an external database defined in the configuration file
 */
class DbFactory {

	/**
	 * The cluster for the database
	 * @var string|false
	 */
	private $cluster;

	/** @var string|false */
	private $shared;

	/** @var string|false */
	private $sharedCluster;

	/**
	 * @param string|false $cluster
	 * @param string|false $shared
	 * @param string|false $sharedCluster
	 */
	public function __construct( $cluster = false, $shared = false, $sharedCluster = false ) {
		$this->cluster = $cluster;
		$this->shared = $shared;
		$this->sharedCluster = $sharedCluster;
	}

	/**
	 * Create a db factory instance from default Echo configuration
	 * A singleton is not necessary because it's actually handled
	 * inside core database object
	 *
	 * @return DbFactory
	 */
	public static function newFromDefault() {
		global $wgEchoCluster, $wgEchoSharedTrackingDB, $wgEchoSharedTrackingCluster;

		return new self( $wgEchoCluster, $wgEchoSharedTrackingDB, $wgEchoSharedTrackingCluster );
	}

	/**
	 * @return bool
	 */
	public function isReadOnly() {
		return ( $this->getLB()->getReadOnlyReason() !== false );
	}

	/**
	 * Get the database load balancer
	 * @return ILoadBalancer
	 */
	protected function getLB() {
		// Use the external db defined for Echo
		if ( $this->cluster ) {
			$lb = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getExternalLB( $this->cluster );
		} else {
			$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		}

		return $lb;
	}

	/**
	 * @return ILoadBalancer
	 */
	protected function getSharedLB() {
		if ( $this->sharedCluster ) {
			$lb = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getExternalLB( $this->sharedCluster );
		} else {
			$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		}

		return $lb;
	}

	/**
	 * Get the database connection for Echo
	 * @param int $db Index of the connection to get
	 * @param string[] $groups Query groups.
	 * @return IDatabase
	 */
	public function getEchoDb( $db, array $groups = [] ) {
		return $this->getLB()->getConnection( $db, $groups );
	}

	/**
	 * @param int $db Index of the connection to get
	 * @param string[] $groups Query groups
	 *
	 * @return bool|IDatabase false if no shared db is configured
	 */
	public function getSharedDb( $db, array $groups = [] ) {
		if ( !$this->shared ) {
			return false;
		}

		return $this->getSharedLB()->getConnection( $db, $groups, $this->shared );
	}

	/**
	 * Wrapper function for LBFactory::getExternalLB/getMainLB, some extensions like MobileFrontend is
	 * using this to issue sql queries against Echo database directly.  This
	 * is totally not accepted and should be updated to use Echo database access
	 * objects
	 *
	 * @deprecated Use newFromDefault() instead to create a db factory
	 * @param int $db Index of the connection to get
	 * @param string[] $groups Query groups.
	 * @param string|bool $wiki The wiki ID, or false for the current wiki
	 * @return IDatabase
	 */
	public static function getDB( $db, array $groups = [], $wiki = false ) {
		global $wgEchoCluster;

		$services = MediaWikiServices::getInstance();

		// Use the external db defined for Echo
		if ( $wgEchoCluster ) {
			$lb = $services->getDBLoadBalancerFactory()->getExternalLB( $wgEchoCluster );
		} else {
			if ( $wiki === false ) {
				$lb = $services->getDBLoadBalancer();
			} else {
				$lb = $services->getDBLoadBalancerFactory()->getMainLB( $wiki );
			}
		}

		return $lb->getConnection( $db, $groups, $wiki );
	}

	/**
	 * Check whether it makes sense to retry a failed lookup on the primary database.
	 * @return bool True if there are multiple servers and changes were made in this request; false otherwise
	 */
	public function canRetryPrimary() {
		return $this->getLB()->getServerCount() > 1 && $this->getLB()->hasOrMadeRecentPrimaryChanges();
	}
}

class_alias( DbFactory::class, 'MWEchoDbFactory' );
