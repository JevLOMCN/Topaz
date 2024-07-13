<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks\Handlers;

use BagOStuff;
use HashBagOStuff;
use MediaWiki\Extension\AbuseFilter\BlockAutopromoteStore;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\User\Hook\GetAutoPromoteGroupsHook;
use MediaWiki\User\UserIdentity;

class AutoPromoteGroupsHandler implements GetAutoPromoteGroupsHook {

	/** @var BagOStuff */
	private $cache;

	/** @var ConsequencesRegistry */
	private $consequencesRegistry;

	/** @var BlockAutopromoteStore */
	private $blockAutopromoteStore;

	/**
	 * @param ConsequencesRegistry $consequencesRegistry
	 * @param BlockAutopromoteStore $blockAutopromoteStore
	 * @param BagOStuff|null $cache
	 */
	public function __construct(
		ConsequencesRegistry $consequencesRegistry,
		BlockAutopromoteStore $blockAutopromoteStore,
		BagOStuff $cache = null
	) {
		$this->cache = $cache ?? new HashBagOStuff();
		$this->consequencesRegistry = $consequencesRegistry;
		$this->blockAutopromoteStore = $blockAutopromoteStore;
	}

	/**
	 * @param UserIdentity $user
	 * @param string[] &$promote
	 */
	public function onGetAutoPromoteGroups( $user, &$promote ): void {
		if (
			in_array( 'blockautopromote', $this->consequencesRegistry->getAllEnabledActionNames() )
			&& $promote
		) {
			// Proxy the blockautopromote data to a faster backend, using an appropriate key
			$quickCacheKey = $this->cache->makeKey(
				'abusefilter',
				'blockautopromote',
				'quick',
				$user->getId()
			);
			$blocked = (bool)$this->cache->getWithSetCallback(
				$quickCacheKey,
				BagOStuff::TTL_PROC_LONG,
				function () use ( $user ) {
					return $this->blockAutopromoteStore->getAutoPromoteBlockStatus( $user );
				}
			);

			if ( $blocked ) {
				$promote = [];
			}
		}
	}
}
