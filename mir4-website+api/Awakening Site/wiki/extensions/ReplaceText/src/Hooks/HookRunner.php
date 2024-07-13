<?php

namespace MediaWiki\Extension\ReplaceText\Hooks;

use MediaWiki\HookContainer\HookContainer;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	FilterPageTitlesForEditHook,
	FilterPageTitlesForRenameHook
{
	private HookContainer $hookContainer;

	/**
	 * Constructor.
	 * @param HookContainer $hookContainer
	 */
	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onReplaceTextFilterPageTitlesForEdit( array &$filteredTitles ) {
		return $this->hookContainer->run(
			'ReplaceTextFilterPageTitlesForEdit',
			[ &$filteredTitles ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onReplaceTextFilterPageTitlesForRename( array &$filteredTitles ) {
		return $this->hookContainer->run(
			'ReplaceTextFilterPageTitlesForRename',
			[ &$filteredTitles ]
		);
	}
}
