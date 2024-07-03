<?php

namespace MediaWiki\Extension\Notifications\Hooks;

use MediaWiki\Revision\RevisionRecord;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "EchoGetEventsForRevision" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface EchoGetEventsForRevisionHook {
	/**
	 * @param array &$events List of event info arrays
	 * @param RevisionRecord $revision
	 * @param bool $isRevert
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onEchoGetEventsForRevision( array &$events, RevisionRecord $revision, bool $isRevert );
}
