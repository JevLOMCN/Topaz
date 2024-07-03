<?php

namespace LoginNotify\Hooks;

use MediaWiki\HookContainer\HookContainer;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	\MediaWiki\Auth\Hook\AuthManagerLoginAuthenticateAuditHook
{
	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onAuthManagerLoginAuthenticateAudit( $response, $user,
		$username, $extraData
	) {
		return $this->hookContainer->run(
			'AuthManagerLoginAuthenticateAudit',
			[ $response, $user, $username, $extraData ]
		);
	}
}
