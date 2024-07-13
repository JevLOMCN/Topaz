<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences\Consequence;

use MediaWiki\User\UserIdentity;

/**
 * Interface for consequences which can be reverted
 */
interface ReversibleConsequence {

	/**
	 * @param UserIdentity $performer
	 * @param string $reason
	 * @return bool Whether the revert was successful
	 */
	public function revert( UserIdentity $performer, string $reason ): bool;

}
