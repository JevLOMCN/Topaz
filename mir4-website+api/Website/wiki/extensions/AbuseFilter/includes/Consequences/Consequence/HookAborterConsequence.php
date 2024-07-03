<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences\Consequence;

/**
 * Interface for consequences that can abort an action hook with an error message
 */
interface HookAborterConsequence {
	/**
	 * Return a message specifier that will be used to fail the hook
	 * @return array First element is the key, then the parameters
	 */
	public function getMessage(): array;
}
