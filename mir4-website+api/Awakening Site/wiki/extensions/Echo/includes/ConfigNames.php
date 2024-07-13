<?php

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
namespace MediaWiki\Extension\Notifications;

/**
 * A class containing constants representing the names of configuration variables,
 * to protect against typos.
 *
 * @since 1.41
 */
class ConfigNames {
	public const CrossWikiNotifications = 'EchoCrossWikiNotifications';
	public const EnableEmailBatch = 'EchoEnableEmailBatch';
	public const EnablePush = 'EchoEnablePush';
	public const NotificationCategories = 'EchoNotificationCategories';
	public const NotificationIcons = 'EchoNotificationIcons';
	public const Notifications = 'EchoNotifications';
	public const Notifiers = 'EchoNotifiers';
	public const PerUserBlacklist = 'EchoPerUserBlacklist';
	public const PollForUpdates = 'EchoPollForUpdates';
	public const SecondaryIcons = 'EchoSecondaryIcons';
	public const WatchlistEmailOncePerPage = 'EchoWatchlistEmailOncePerPage';
	public const WatchlistNotifications = 'EchoWatchlistNotifications';
}
