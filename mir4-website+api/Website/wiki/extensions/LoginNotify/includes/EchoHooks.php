<?php

namespace LoginNotify;

use MediaWiki\Extension\Notifications\AttributeManager;
use MediaWiki\Extension\Notifications\Hooks\BeforeCreateEchoEventHook;
use MediaWiki\Extension\Notifications\Hooks\EchoGetBundleRulesHook;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\Notifications\UserLocator;

/**
 * Hooks from Echo extension,
 * which is optional to use with this extension.
 */
class EchoHooks implements
	BeforeCreateEchoEventHook,
	EchoGetBundleRulesHook
{
	/**
	 * Add LoginNotify events to Echo
	 *
	 * @param string[] &$notifications Array of Echo notifications
	 * @param string[] &$notificationCategories Array of Echo notification categories
	 * @param string[] &$icons Array of icon details
	 */
	public function onBeforeCreateEchoEvent(
		array &$notifications,
		array &$notificationCategories,
		array &$icons
	) {
		global $wgLoginNotifyEnableOnSuccess, $wgNotifyTypeAvailabilityByCategory;

		$icons['LoginNotify-user-avatar'] = [
			'path' => 'LoginNotify/UserAvatar.svg'
		];

		$notificationCategories['login-fail'] = [
			'priority' => 7,
			'tooltip' => 'echo-pref-tooltip-login-fail',
		];

		$loginBase = [
			AttributeManager::ATTR_LOCATORS => [
				[ [ UserLocator::class, 'locateEventAgent' ] ],
			],
			'canNotifyAgent' => true,
			'category' => 'login-fail',
			'group' => 'negative',
			'presentation-model' => PresentationModel::class,
			'icon' => 'LoginNotify-user-avatar',
			'immediate' => true,
		];
		$notifications['login-fail-new'] = [
			'bundle' => [
				'web' => true,
				'expandable' => false
			]
		] + $loginBase;
		$notifications['login-fail-known'] = [
			'bundle' => [
				'web' => true,
				'expandable' => false
			]
		] + $loginBase;
		if ( $wgLoginNotifyEnableOnSuccess ) {
			$notificationCategories['login-success'] = [
				'priority' => 7,
				'tooltip' => 'echo-pref-tooltip-login-success',
			];
			$notifications['login-success'] = [
				'category' => 'login-success',
			] + $loginBase;
			$wgNotifyTypeAvailabilityByCategory['login-success'] = [
				'web' => false,
				'email' => true,
			];
		}
	}

	/**
	 * @param Event $event
	 * @param string &$bundleString
	 */
	public function onEchoGetBundleRules( Event $event, string &$bundleString ) {
		switch ( $event->getType() ) {
			case 'login-fail-new':
				$bundleString = 'login-fail';
				break;
		}
	}
}
