<?php

namespace MediaWiki\Extension\TitleBlacklist;

use MediaWiki\Auth\AuthenticationRequest;

/**
 * An authentication request that allows users with sufficiently high privileges to skip the
 * title blacklist check.
 */
class TitleBlacklistAuthenticationRequest extends AuthenticationRequest {
	public $ignoreTitleBlacklist;

	public function getFieldInfo() {
		return [
			'ignoreTitleBlacklist' => [
				'type' => 'checkbox',
				'label' => wfMessage( 'titleblacklist-override' ),
				'help' => wfMessage( 'titleblacklist-override-help' ),
				'optional' => true,
			],
		];
	}
}
