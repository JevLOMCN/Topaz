<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences\Consequence;

/**
 * Interface for consequences that are checked first, and can disable every other consequence (including
 * other ConsequencesDisabler consequences) if needed.
 */
interface ConsequencesDisablerConsequence {
	/**
	 * Returns whether other consequences should be disabled. This may depend on Consequence::execute().
	 * ConsequenceNotPrecheckedException can be used to assert that execute() was called.
	 * @return bool
	 */
	public function shouldDisableOtherConsequences(): bool;

	/**
	 * Returns an arbitrary integer representing the sorting importance of this consequence. Consequences
	 * with lower numbers are executed first.
	 * @note If two consequences have the same importance, their final order is nondeterministic
	 * @return int
	 */
	public function getSort(): int;
}
