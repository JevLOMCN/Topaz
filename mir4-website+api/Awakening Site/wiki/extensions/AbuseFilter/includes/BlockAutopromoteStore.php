<?php

namespace MediaWiki\Extension\AbuseFilter;

use BagOStuff;
use ManualLogEntry;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerInterface;

/**
 * Class responsible for storing and retrieving blockautopromote status
 */
class BlockAutopromoteStore {

	public const SERVICE_NAME = 'AbuseFilterBlockAutopromoteStore';

	/**
	 * @var BagOStuff
	 */
	private $store;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/** @var FilterUser */
	private $filterUser;

	/**
	 * @param BagOStuff $store
	 * @param LoggerInterface $logger
	 * @param FilterUser $filterUser
	 */
	public function __construct( BagOStuff $store, LoggerInterface $logger, FilterUser $filterUser ) {
		$this->store = $store;
		$this->logger = $logger;
		$this->filterUser = $filterUser;
	}

	/**
	 * Gets the autopromotion block status for the given user
	 *
	 * @param UserIdentity $target
	 * @return int
	 */
	public function getAutoPromoteBlockStatus( UserIdentity $target ): int {
		return (int)$this->store->get( $this->getAutoPromoteBlockKey( $target ) );
	}

	/**
	 * Blocks autopromotion for the given user
	 *
	 * @param UserIdentity $target
	 * @param string $msg The message to show in the log
	 * @param int $duration Duration for which autopromotion is blocked, in seconds
	 * @return bool True on success, false on failure
	 */
	public function blockAutoPromote( UserIdentity $target, string $msg, int $duration ): bool {
		if ( !$this->store->set(
			$this->getAutoPromoteBlockKey( $target ),
			1,
			$duration
		) ) {
			// Failed to set key
			$this->logger->warning(
				'Failed to block autopromotion for {target}. Error: {error}',
				[
					'target' => $target->getName(),
					'error' => $this->store->getLastError(),
				]
			);
			return false;
		}

		$logEntry = new ManualLogEntry( 'rights', 'blockautopromote' );
		$logEntry->setPerformer( $this->filterUser->getUserIdentity() );
		$logEntry->setTarget( new TitleValue( NS_USER, $target->getName() ) );

		$logEntry->setParameters( [
			'7::duration' => $duration,
			// These parameters are unused in our message, but some parts of the code check for them
			'4::oldgroups' => [],
			'5::newgroups' => []
		] );
		$logEntry->setComment( $msg );
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			// FIXME Remove this check once ManualLogEntry is servicified (T253717)
			// @codeCoverageIgnoreStart
			$logEntry->publish( $logEntry->insert() );
			// @codeCoverageIgnoreEnd
		}

		return true;
	}

	/**
	 * Unblocks autopromotion for the given user
	 *
	 * @param UserIdentity $target
	 * @param UserIdentity $performer
	 * @param string $msg The message to show in the log
	 * @return bool True on success, false on failure
	 */
	public function unblockAutopromote( UserIdentity $target, UserIdentity $performer, string $msg ): bool {
		// Immediately expire (delete) the key, failing if it does not exist
		$expireAt = time() - BagOStuff::TTL_HOUR;
		if ( !$this->store->changeTTL(
			$this->getAutoPromoteBlockKey( $target ),
			$expireAt
		) ) {
			// Key did not exist to begin with; nothing to do
			return false;
		}

		$logEntry = new ManualLogEntry( 'rights', 'restoreautopromote' );
		$logEntry->setTarget( new TitleValue( NS_USER, $target->getName() ) );
		$logEntry->setComment( $msg );
		// These parameters are unused in our message, but some parts of the code check for them
		$logEntry->setParameters( [
			'4::oldgroups' => [],
			'5::newgroups' => []
		] );
		$logEntry->setPerformer( $performer );
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			// FIXME Remove this check once ManualLogEntry is servicified (T253717)
			// @codeCoverageIgnoreStart
			$logEntry->publish( $logEntry->insert() );
			// @codeCoverageIgnoreEnd
		}

		return true;
	}

	/**
	 * @param UserIdentity $target
	 * @return string
	 */
	private function getAutoPromoteBlockKey( UserIdentity $target ): string {
		return $this->store->makeKey( 'abusefilter', 'block-autopromote', $target->getId() );
	}
}
