<?php

namespace MediaWiki\Extension\Nuke\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Title\Title;

/**
 * Handle running Nuke's hooks
 * @author DannyS712
 */
class NukeHookRunner implements NukeDeletePageHook, NukeGetNewPagesHook {

	/** @var HookContainer */
	private $hookContainer;

	/**
	 * @param HookContainer $hookContainer
	 */
	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onNukeDeletePage( Title $title, string $reason, bool &$deletionResult ) {
		return $this->hookContainer->run(
			'NukeDeletePage',
			[ $title, $reason, &$deletionResult ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onNukeGetNewPages(
		string $username,
		?string $pattern,
		?int $namespace,
		int $limit,
		array &$pages
	) {
		return $this->hookContainer->run(
			'NukeGetNewPages',
			[ $username, $pattern, $namespace, $limit, &$pages ]
		);
	}

}
