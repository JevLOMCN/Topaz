<?php

namespace MediaWiki\Extension\Notifications\Hooks;

use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\Notifications\Model\Notification;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	BeforeCreateEchoEventHook,
	BeforeDisplayOrangeAlertHook,
	BeforeEchoEventInsertHook,
	EchoAbortEmailNotificationHook,
	EchoCanAbortNewMessagesAlertHook,
	EchoCreateNotificationCompleteHook,
	EchoGetBundleRulesHook,
	EchoGetDefaultNotifiedUsersHook,
	EchoGetEventsForRevisionHook,
	EchoGetNotificationTypesHook,
	EventInsertCompleteHook
{
	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforeCreateEchoEvent(
		array &$notifications,
		array &$notificationCategories,
		array &$notificationIcons
	) {
		return $this->hookContainer->run(
			'BeforeCreateEchoEvent',
			[ &$notifications, &$notificationCategories, &$notificationIcons ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforeDisplayOrangeAlert( User $user, Title $title ) {
		return $this->hookContainer->run(
			'BeforeDisplayOrangeAlert',
			[ $user, $title ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onBeforeEchoEventInsert( Event $event ) {
		return $this->hookContainer->run(
			'BeforeEchoEventInsert',
			[ $event ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onEchoAbortEmailNotification( UserIdentity $user, Event $event ) {
		return $this->hookContainer->run(
			'EchoAbortEmailNotification',
			[ $user, $event ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onEchoCanAbortNewMessagesAlert() {
		return $this->hookContainer->run(
			'EchoCanAbortNewMessagesAlert'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onEchoCreateNotificationComplete( Notification $notification ) {
		return $this->hookContainer->run(
			'EchoCreateNotificationComplete',
			[ $notification ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onEchoGetBundleRules( Event $event, string &$bundleKey ) {
		return $this->hookContainer->run(
			'EchoGetBundleRules',
			[ $event, &$bundleKey ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onEchoGetDefaultNotifiedUsers( Event $event, array &$users ) {
		return $this->hookContainer->run(
			'EchoGetDefaultNotifiedUsers',
			[ $event, &$users ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onEchoGetEventsForRevision( array &$events, RevisionRecord $revision, bool $isRevert ) {
		return $this->hookContainer->run(
			'EchoGetEventsForRevision',
			[ &$events, $revision, $isRevert ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onEchoGetNotificationTypes( User $user, Event $event, array &$userNotifyTypes ) {
		return $this->hookContainer->run(
			'EchoGetNotificationTypes',
			[ $user, $event, &$userNotifyTypes ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onEventInsertComplete( Event $event ) {
		return $this->hookContainer->run(
			'EventInsertComplete',
			[ $event ]
		);
	}
}
