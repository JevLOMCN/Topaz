<?php

namespace MediaWiki\Extension\Notifications;

use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentity;

/**
 * An object that manages attributes of echo notifications: category, eligibility,
 * group, section etc.
 */
class AttributeManager {
	/**
	 * @var UserGroupManager
	 */
	private $userGroupManager;

	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	/**
	 * @var array[]
	 */
	protected $notifications;

	/**
	 * @var array[]
	 */
	protected $categories;

	/**
	 * @var bool[]
	 */
	protected $defaultNotifyTypeAvailability;

	/**
	 * @var array[]
	 */
	protected $notifyTypeAvailabilityByCategory;

	/**
	 * Notification section constant
	 */
	public const ALERT = 'alert';
	public const MESSAGE = 'message';
	public const ALL = 'all';

	/** @var string */
	protected const DEFAULT_SECTION = self::ALERT;

	/**
	 * Notifications are broken down to two sections, default is alert
	 * @var string[]
	 */
	public static $sections = [
		self::ALERT,
		self::MESSAGE
	];

	/**
	 * Names for keys in $wgEchoNotifications notification config
	 */
	public const ATTR_LOCATORS = 'user-locators';
	public const ATTR_FILTERS = 'user-filters';

	/**
	 * @param array[] $notifications Notification attributes
	 * @param array[] $categories Notification categories
	 * @param bool[] $defaultNotifyTypeAvailability Associative array with output
	 *   formats as keys and whether they are available as boolean values.
	 * @param array[] $notifyTypeAvailabilityByCategory Associative array with
	 *   categories as keys and value an associative array as with
	 *   $defaultNotifyTypeAvailability.
	 * @param UserGroupManager $userGroupManager
	 * @param UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		array $notifications,
		array $categories,
		array $defaultNotifyTypeAvailability,
		array $notifyTypeAvailabilityByCategory,
		UserGroupManager $userGroupManager,
		UserOptionsLookup $userOptionsLookup
	) {
		// Extensions can define their own notifications and categories
		$this->notifications = $notifications;
		$this->categories = $categories;

		$this->defaultNotifyTypeAvailability = $defaultNotifyTypeAvailability;
		$this->notifyTypeAvailabilityByCategory = $notifyTypeAvailabilityByCategory;
		$this->userGroupManager = $userGroupManager;
		$this->userOptionsLookup = $userOptionsLookup;
	}

	/**
	 * Get the user-locators|user-filters related to the provided event type
	 *
	 * @param string $type
	 * @param string $locator Either self::ATTR_LOCATORS or self::ATTR_FILTERS
	 * @return array
	 */
	public function getUserCallable( $type, $locator = self::ATTR_LOCATORS ) {
		if ( isset( $this->notifications[$type][$locator] ) ) {
			return (array)$this->notifications[$type][$locator];
		}

		return [];
	}

	/**
	 * Get the enabled events for a user, which excludes user-dismissed events
	 * from the general enabled events
	 * @param UserIdentity $userIdentity
	 * @param string|string[] $notifierTypes a defined notifier type, or an array containing one
	 *   or more defined notifier types
	 * @return string[]
	 */
	public function getUserEnabledEvents( UserIdentity $userIdentity, $notifierTypes ) {
		if ( is_string( $notifierTypes ) ) {
			$notifierTypes = [ $notifierTypes ];
		}
		return array_values( array_filter(
			array_keys( $this->notifications ),
			function ( $eventType ) use ( $userIdentity, $notifierTypes ) {
				$category = $this->getNotificationCategory( $eventType );
				return $this->getCategoryEligibility( $userIdentity, $category ) &&
					array_reduce( $notifierTypes, function ( $prev, $type ) use ( $userIdentity, $category ) {
						return $prev ||
							(
								$this->isNotifyTypeAvailableForCategory( $category, $type ) &&
								$this->userOptionsLookup->getOption(
									$userIdentity,
									"echo-subscriptions-$type-$category"
								)
							);
					}, false );
			}
		) );
	}

	/**
	 * Get the user enabled events for the specified sections
	 * @param UserIdentity $userIdentity
	 * @param string|string[] $notifierTypes a defined notifier type, or an array containing one
	 *   or more defined notifier types
	 * @param string[] $sections
	 * @return string[]
	 */
	public function getUserEnabledEventsBySections(
		UserIdentity $userIdentity,
		$notifierTypes,
		array $sections
	) {
		$events = [];
		foreach ( $sections as $section ) {
			$events = array_merge(
				$events,
				$this->getEventsForSection( $section )
			);
		}

		return array_intersect(
			$this->getUserEnabledEvents( $userIdentity, $notifierTypes ),
			$events
		);
	}

	/**
	 * Gets events (notification types) for a given section
	 *
	 * @param string $section Internal section name, one of the values from self::$sections
	 *
	 * @return string[] Array of notification types in this section
	 */
	public function getEventsForSection( $section ) {
		$events = [];

		$isDefault = ( $section === self::DEFAULT_SECTION );

		foreach ( $this->notifications as $event => $attribs ) {
			if (
				(
					isset( $attribs['section'] ) &&
					$attribs['section'] === $section
				) ||
				(
					$isDefault &&
					(
						!isset( $attribs['section'] ) ||

						// Invalid section
						!in_array( $attribs['section'], self::$sections )
					)
				)

			) {
				$events[] = $event;
			}
		}

		return $events;
	}

	/**
	 * Gets array of internal category names
	 *
	 * @return string[] All internal names
	 */
	public function getInternalCategoryNames() {
		return array_keys( $this->categories );
	}

	/**
	 * See if a user is eligible to receive a certain type of notification
	 * (based on user groups, not user preferences)
	 *
	 * @param UserIdentity $userIdentity
	 * @param string $category A notification category defined in $wgEchoNotificationCategories
	 * @return bool
	 */
	public function getCategoryEligibility( UserIdentity $userIdentity, $category ) {
		$usersGroups = $this->userGroupManager->getUserGroups( $userIdentity );
		if ( isset( $this->categories[$category]['usergroups'] ) ) {
			$allowedGroups = $this->categories[$category]['usergroups'];
			if ( !array_intersect( $usersGroups, $allowedGroups ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the priority for a specific notification type
	 *
	 * @param string $notificationType A notification type defined in $wgEchoNotifications
	 * @return int From 1 to 10 (10 is default)
	 */
	public function getNotificationPriority( $notificationType ) {
		$category = $this->getNotificationCategory( $notificationType );

		return $this->getCategoryPriority( $category );
	}

	/**
	 * Get the priority for a notification category
	 *
	 * @param string $category A notification category defined in $wgEchoNotificationCategories
	 * @return int From 1 to 10 (10 is default)
	 */
	public function getCategoryPriority( $category ) {
		if ( isset( $this->categories[$category]['priority'] ) ) {
			$priority = $this->categories[$category]['priority'];
			if ( $priority >= 1 && $priority <= 10 ) {
				return $priority;
			}
		}

		return 10;
	}

	/**
	 * Get the notification category for a notification type
	 *
	 * @param string $notificationType A notification type defined in $wgEchoNotifications
	 * @return string The name of the notification category or 'other' if no
	 *     category is explicitly assigned.
	 */
	public function getNotificationCategory( $notificationType ) {
		if ( isset( $this->notifications[$notificationType]['category'] ) ) {
			$category = $this->notifications[$notificationType]['category'];
			if ( isset( $this->categories[$category] ) ) {
				return $category;
			}
		}

		return 'other';
	}

	/**
	 * Gets an associative array mapping categories to the notification types in
	 * the category
	 *
	 * @return array[] Associative array with category as key
	 */
	public function getEventsByCategory() {
		$eventsByCategory = [];

		foreach ( $this->categories as $category => $categoryDetails ) {
			$eventsByCategory[$category] = [];
		}

		foreach ( $this->notifications as $notificationType => $notificationDetails ) {
			$category = $notificationDetails['category'];
			if ( isset( $eventsByCategory[$category] ) ) {
				// Only real categories.  Currently, this excludes the 'foreign'
				// pseudo-category.
				$eventsByCategory[$category][] = $notificationType;
			}
		}

		return $eventsByCategory;
	}

	/**
	 * Get notify type availability for all notify types for a given category.
	 *
	 * This means whether users *can* turn notifications for this category and format
	 * on, regardless of the default or a particular user's preferences.
	 *
	 * @param string $category Category name
	 * @return array [ 'web' => bool, 'email' => bool ]
	 */
	public function getNotifyTypeAvailabilityForCategory( $category ) {
		return array_merge(
			$this->defaultNotifyTypeAvailability,
			$this->notifyTypeAvailabilityByCategory[$category] ?? []
		);
	}

	/**
	 * Checks whether the specified notify type is available for the specified
	 * category.
	 *
	 * This means whether users *can* turn notifications for this category and format
	 * on, regardless of the default or a particular user's preferences.
	 *
	 * @param string $category Category name
	 * @param string $notifyType notify type, e.g. email/web.
	 * @return bool
	 */
	public function isNotifyTypeAvailableForCategory( $category, $notifyType ) {
		return $this->getNotifyTypeAvailabilityForCategory( $category )[$notifyType];
	}

	/**
	 * Checks whether category is displayed in preferences
	 *
	 * @param string $category Category name
	 * @return bool
	 */
	public function isCategoryDisplayedInPreferences( $category ) {
		return !(
			isset( $this->categories[$category]['no-dismiss'] ) &&
			in_array( 'all', $this->categories[$category]['no-dismiss'] )
		);
	}

	/**
	 * Checks whether the specified notify type is dismissable for the specified
	 * category.
	 *
	 * This means whether the user is allowed to opt out of receiving notifications
	 * for this category and format.
	 *
	 * @param string $category Name of category
	 * @param string $notifyType notify type, e.g. email/web.
	 * @return bool
	 */
	public function isNotifyTypeDismissableForCategory( $category, $notifyType ) {
		return !(
			isset( $this->categories[$category]['no-dismiss'] ) &&
			(
				in_array( 'all', $this->categories[$category]['no-dismiss'] ) ||
				in_array( $notifyType, $this->categories[$category]['no-dismiss'] )
			)
		);
	}

	/**
	 * Get notification section for a notification type
	 * @param string $notificationType
	 * @return string
	 */
	public function getNotificationSection( $notificationType ) {
		return $this->notifications[$notificationType]['section'] ?? self::DEFAULT_SECTION;
	}

	/**
	 * Get notification types that allow their own agent to be notified.
	 *
	 * @return string[] Notification types
	 */
	public function getNotifyAgentEvents() {
		$events = [];
		foreach ( $this->notifications as $event => $attribs ) {
			if ( $attribs['canNotifyAgent'] ?? false ) {
				$events[] = $event;
			}
		}
		return $events;
	}

	/**
	 * @param string $type
	 * @return bool Whether a notification type can be an expandable bundle
	 */
	public function isBundleExpandable( $type ) {
		return $this->notifications[$type]['bundle']['expandable'] ?? false;
	}

}

class_alias( AttributeManager::class, 'EchoAttributeManager' );
