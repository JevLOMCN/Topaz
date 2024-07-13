<?php

namespace LoginNotify;

use MediaWiki\Title\Title;

class PurgeSeenJob extends \Job {
	private $loginNotify;

	public function __construct( Title $title, array $params, LoginNotify $loginNotify ) {
		parent::__construct( 'LoginNotifyPurgeSeen', $title, $params );
		$this->loginNotify = $loginNotify;
	}

	public function run() {
		$minId = $this->getParams()['minId'];
		$this->loginNotify->purgeSeen( $minId );
		return true;
	}
}
