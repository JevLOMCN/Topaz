<?php

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use IContextSource;
use MediaWiki\Config\Config;
use MediaWiki\HookContainer\HookContainer;
use MessageLocalizer;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	DiscussionToolsTermsOfUseMessagesHook,
	DiscussionToolsAddOverflowMenuItemsHook
{
	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onDiscussionToolsTermsOfUseMessages( array &$messages, MessageLocalizer $context, Config $config ) {
		return $this->hookContainer->run(
			'DiscussionToolsTermsOfUseMessages',
			[ &$messages, $context, $config ]
		);
	}

	/** @inheritDoc */
	public function onDiscussionToolsAddOverflowMenuItems(
		array &$overflowMenuItems,
		array &$resourceLoaderModules,
		array $threadItemData,
		IContextSource $contextSource
	) {
		return $this->hookContainer->run(
			'DiscussionToolsAddOverflowMenuItems',
			[
				&$overflowMenuItems,
				&$resourceLoaderModules,
				$threadItemData,
				$contextSource,
			]
		);
	}
}
