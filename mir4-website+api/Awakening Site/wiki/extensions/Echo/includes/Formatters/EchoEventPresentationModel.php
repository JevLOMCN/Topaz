<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use InvalidArgumentException;
use JsonSerializable;
use Language;
use MediaWiki\Extension\Notifications\Controller\NotificationController;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\WikiMap\WikiMap;
use Message;
use MessageLocalizer;
use MessageSpecifier;
use Wikimedia\Timestamp\TimestampException;

/**
 * Class that returns structured data based
 * on the provided event.
 */
abstract class EchoEventPresentationModel implements JsonSerializable, MessageLocalizer {

	/**
	 * Recommended length of usernames included in messages, in
	 * characters (not bytes).
	 */
	private const USERNAME_RECOMMENDED_LENGTH = 20;

	/**
	 * Recommended length of usernames used as link label, in
	 * characters (not bytes).
	 */
	private const USERNAME_AS_LABEL_RECOMMENDED_LENGTH = 15;

	/**
	 * Recommended length of page names included in messages, in
	 * characters (not bytes).
	 */
	protected const PAGE_NAME_RECOMMENDED_LENGTH = 50;

	/**
	 * Recommended length of page names used as link label, in
	 * characters (not bytes).
	 */
	private const PAGE_NAME_AS_LABEL_RECOMMENDED_LENGTH = 15;

	/**
	 * Recommended length of section titles included in messages, in
	 * characters (not bytes).
	 */
	public const SECTION_TITLE_RECOMMENDED_LENGTH = 50;

	/**
	 * @var Event
	 */
	protected $event;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var User for permissions checking
	 */
	private $user;

	/**
	 * @var string 'web' or 'email'
	 */
	private $distributionType;

	/**
	 * @param Event $event
	 * @param Language $language
	 * @param User $user Only used for permissions checking and GENDER
	 * @param string $distributionType
	 */
	protected function __construct(
		Event $event,
		Language $language,
		User $user,
		$distributionType
	) {
		$this->event = $event;
		$this->type = $event->getType();
		$this->language = $language;
		$this->user = $user;
		$this->distributionType = $distributionType;
	}

	/**
	 * Convenience function to detect whether the event type
	 * has a presentation model available for rendering
	 *
	 * @param string $type event type
	 * @return bool
	 */
	public static function supportsPresentationModel( $type ) {
		global $wgEchoNotifications;
		return isset( $wgEchoNotifications[$type]['presentation-model'] )
			&& class_exists( $wgEchoNotifications[$type]['presentation-model'] );
	}

	/**
	 * @param Event $event
	 * @param Language $language
	 * @param User $user
	 * @param string $distributionType 'web' or 'email'
	 * @return EchoEventPresentationModel
	 */
	public static function factory(
		Event $event,
		Language $language,
		User $user,
		$distributionType = 'web'
	) {
		global $wgEchoNotifications;
		// @todo don't depend upon globals

		$class = $wgEchoNotifications[$event->getType()]['presentation-model'];
		return new $class( $event, $language, $user, $distributionType );
	}

	/**
	 * Get the type of event
	 *
	 * @return string
	 */
	final public function getType() {
		return $this->type;
	}

	/**
	 * Get the user receiving the notification
	 *
	 * @return User
	 */
	final public function getUser() {
		return $this->user;
	}

	/**
	 * Get the category of event
	 *
	 * @return string
	 */
	final public function getCategory() {
		return $this->event->getCategory();
	}

	/**
	 * @return string 'web' or 'email'
	 */
	final public function getDistributionType() {
		return $this->distributionType;
	}

	/**
	 * Equivalent to IContextSource::msg for the current
	 * language
	 *
	 * @param string|string[]|MessageSpecifier $key Message key, or array of keys,
	 *   or a MessageSpecifier.
	 * @param mixed ...$params Normal message parameters
	 * @return Message
	 */
	public function msg( $key, ...$params ) {
		/**
		 * @var Message $msg
		 */
		$msg = wfMessage( $key, ...$params );
		$msg->inLanguage( $this->language );

		// Notifications are considered UI (and should be in UI language, not
		// content), and this flag is set false by inLanguage.
		$msg->setInterfaceMessageFlag( true );

		return $msg;
	}

	/**
	 * @return Event[]
	 */
	final protected function getBundledEvents() {
		return $this->event->getBundledEvents() ?: [];
	}

	/**
	 * Get the ids of the bundled notifications or false if it's not bundled
	 *
	 * @return int[]|false
	 */
	public function getBundledIds() {
		if ( $this->isBundled() ) {
			return array_map( static function ( Event $event ) {
				return $event->getId();
			}, $this->getBundledEvents() );
		}
		return false;
	}

	/**
	 * This method returns true when there are bundled notifications, even if they are all
	 * in the same group according to getBundleGrouping(). For presentation purposes, you may
	 * want to check if getBundleCount( true, $yourCallback ) > 1 instead.
	 *
	 * @return bool Whether there are other notifications bundled with this one.
	 */
	final protected function isBundled() {
		return $this->getBundleCount() > 1;
	}

	/**
	 * Count the number of event groups in this bundle.
	 *
	 * By default, each event is in its own group, and this method returns the number of events.
	 * To group events differently, pass $groupCallback. For example, to group events with the
	 * same title together, use $callback = function ( $event ) { return $event->getTitle()->getPrefixedText(); }
	 *
	 * If $includeCurrent is false, all events in the same group as the current one will be ignored.
	 *
	 * @param bool $includeCurrent Include the current event (and its group)
	 * @param callable|null $groupCallback Callback that takes an Event and returns a grouping value
	 * @return int Number of bundled events or groups
	 * @throws InvalidArgumentException
	 */
	final protected function getBundleCount( $includeCurrent = true, $groupCallback = null ) {
		$events = array_merge( $this->getBundledEvents(), [ $this->event ] );
		if ( $groupCallback ) {
			if ( !is_callable( $groupCallback ) ) {
				// If we pass an invalid callback to array_map(), it'll just throw a warning
				// and return NULL, so $count ends up being 0 or -1. Instead of doing that,
				// throw an exception.
				throw new InvalidArgumentException( 'Invalid callback passed to getBundleCount' );
			}
			$events = array_unique( array_map( $groupCallback, $events ) );
		}
		$count = count( $events );

		if ( !$includeCurrent ) {
			$count--;
		}
		return $count;
	}

	/**
	 * Return the count of notifications bundled together.
	 *
	 * For parameters, see {@see EchoEventPresentationModel::getBundleCount}.
	 *
	 * @param bool $includeCurrent
	 * @param callable|null $groupCallback
	 * @return int count
	 */
	final protected function getNotificationCountForOutput( $includeCurrent = true, $groupCallback = null ) {
		$count = $this->getBundleCount( $includeCurrent, $groupCallback );
		return NotificationController::getCappedNotificationCount( $count );
	}

	/**
	 * @return string The symbolic icon name as defined in $wgEchoNotificationIcons
	 */
	abstract public function getIconType();

	/**
	 * @return string Timestamp the event occurred at
	 */
	final public function getTimestamp() {
		return $this->event->getTimestamp();
	}

	/**
	 * Helper for Event::userCan
	 *
	 * @param int $type RevisionRecord::DELETED_* constant
	 * @return bool
	 */
	final protected function userCan( $type ) {
		return $this->event->userCan( $type, $this->user );
	}

	/**
	 * @return string[]|false ['wikitext to display', 'username for GENDER'], false if no agent
	 *
	 * We have to display wikitext so we can add CSS classes for revision deleted user.
	 * The goal of this function is for callers not to worry about whether
	 * the user is visible or not.
	 * @par Example:
	 * @code
	 * [ $formattedName, $genderName ] = $this->getAgentForOutput();
	 * $msg->params( $formattedName, $genderName );
	 * @endcode
	 */
	final protected function getAgentForOutput() {
		$agent = $this->event->getAgent();
		if ( !$agent ) {
			return false;
		}

		if ( $this->userCan( RevisionRecord::DELETED_USER ) ) {
			// Not deleted
			return [
				$this->getTruncatedUsername( $agent ),
				$agent->getName()
			];
		} else {
			// Deleted/hidden
			$msg = $this->msg( 'rev-deleted-user' )->plain();
			// HACK: Pass an invalid username to GENDER to force the default
			return [ '<span class="history-deleted">' . $msg . '</span>', '[]' ];
		}
	}

	/**
	 * Return a message with the given key and the agent's
	 * formatted name and name for GENDER as 1st and
	 * 2nd parameters.
	 * @param string $key
	 * @return Message
	 */
	final protected function getMessageWithAgent( $key ) {
		$msg = $this->msg( $key );
		[ $formattedName, $genderName ] = $this->getAgentForOutput();
		$msg->params( $formattedName, $genderName );
		return $msg;
	}

	/**
	 * Get the viewing user's name for usage in GENDER
	 *
	 * @return string
	 */
	final protected function getViewingUserForGender() {
		return $this->user->getName();
	}

	/**
	 * @return array|null Link object to the user's page or Special:Contributions for anon users.
	 *               Can be used for primary or secondary links.
	 *               Same format as secondary link.
	 *               Returns null if the current user cannot see the agent.
	 */
	final protected function getAgentLink() {
		return $this->getUserLink( $this->event->getAgent() );
	}

	/**
	 * To be overridden by subclasses if they are unable to render the
	 * notification, for example when a page is deleted.
	 * If this function returns false, no other methods will be called
	 * on the object.
	 *
	 * @return bool
	 */
	public function canRender() {
		return true;
	}

	/**
	 * @return string Message key that will be used in getHeaderMessage
	 */
	protected function getHeaderMessageKey() {
		return "notification-header-{$this->type}";
	}

	/**
	 * Get a message object and add the performer's name as
	 * a parameter. It is expected that subclasses will override
	 * this.
	 *
	 * @return Message
	 */
	public function getHeaderMessage() {
		return $this->getMessageWithAgent( $this->getHeaderMessageKey() );
	}

	/**
	 * @return string Message key that will be used in getCompactHeaderMessage
	 */
	public function getCompactHeaderMessageKey() {
		return "notification-compact-header-{$this->type}";
	}

	/**
	 * Get a message object and add the performer's name as
	 * a parameter. It is expected that subclasses will override
	 * this.
	 *
	 * This message should be more compact than the header message
	 * ( getHeaderMessage() ). It is displayed when a
	 * notification is part of an expanded bundle.
	 *
	 * @return Message
	 */
	public function getCompactHeaderMessage() {
		$msg = $this->getMessageWithAgent( $this->getCompactHeaderMessageKey() );
		if ( $msg->isDisabled() ) {
			// Back-compat for models that haven't been updated yet
			$msg = $this->getHeaderMessage();
		}

		return $msg;
	}

	/**
	 * @return string Message key that will be used in getSubjectMessage
	 */
	protected function getSubjectMessageKey() {
		return "notification-subject-{$this->type}";
	}

	/**
	 * Get a message object and add the performer's name as
	 * a parameter. It is expected that subclasses will override
	 * this. The output of the message should be plaintext.
	 *
	 * This message is used as the subject line in
	 * single-notification emails.
	 *
	 * For backward compatibility, if this is not defined,
	 * the header message ( getHeaderMessage() ) is used instead.
	 *
	 * @return Message
	 */
	public function getSubjectMessage() {
		$msg = $this->getMessageWithAgent( $this->getSubjectMessageKey() );
		$msg->params( $this->getViewingUserForGender() );
		if ( $msg->isDisabled() ) {
			// Back-compat for models that haven't been updated yet
			$msg = $this->getHeaderMessage();
		}

		return $msg;
	}

	/**
	 * Get a message for the notification's body, false if it has no body
	 *
	 * @return bool|Message
	 */
	public function getBodyMessage() {
		return false;
	}

	/**
	 * Array of primary link details, with possibly-relative URL & label.
	 *
	 * @return array|false Array of link data, or false for no link:
	 *                    ['url' => (string) url, 'label' => (string) link text (non-escaped)]
	 */
	abstract public function getPrimaryLink();

	/**
	 * Like getPrimaryLink(), but with the URL altered to add ?markasread=XYZ. When this link is followed,
	 * the notification is marked as read.
	 *
	 * If the notification is a bundle, the notification IDs are added to the parameter value
	 * separated by a "|". If cross-wiki notifications are enabled, a markasreadwiki parameter is
	 * added.
	 *
	 * @return array|false
	 */
	final public function getPrimaryLinkWithMarkAsRead() {
		global $wgEchoCrossWikiNotifications;
		$primaryLink = $this->getPrimaryLink();
		if ( $primaryLink ) {
			$eventIds = [ $this->event->getId() ];
			if ( $this->getBundledIds() ) {
				$eventIds = array_merge( $eventIds, $this->getBundledIds() );
			}

			$queryParams = [ 'markasread' => implode( '|', $eventIds ) ];
			if ( $wgEchoCrossWikiNotifications ) {
				$queryParams['markasreadwiki'] = WikiMap::getCurrentWikiId();
			}

			$primaryLink['url'] = wfAppendQuery( $primaryLink['url'], $queryParams );
		}
		return $primaryLink;
	}

	/**
	 * Array of secondary link details, including possibly-relative URLs, label,
	 * description & icon name.
	 *
	 * @return (null|array)[] Array of links in the format of:
	 *               [['url' => (string) url,
	 *                 'label' => (string) link text (non-escaped),
	 *                 'description' => (string) descriptive text (optional, non-escaped),
	 *                 'icon' => (bool|string) symbolic ooui icon name (or false if there is none),
	 *                 'type' => (string) optional action type. Used to note a dynamic action,
	 *                           by setting it to 'dynamic-action'
	 *                 'data' => (array) optional array containing information about the dynamic
	 *                           action. It must include 'tokenType' (string), 'messages' (array)
	 *                           with messages supplied for the item and the confirmation dialog
	 *                           and 'params' (array) for the API operation needed to complete the
	 *                           action. For example:
	 *                 'data' => [
	 *                     'tokenType' => 'watch',
	 *                     'params' => [
	 *                         'action' => 'watch',
	 *                         'titles' => 'Namespace:SomeTitle'
	 *                     ],
	 *                     'messages' => [
	 *                         'confirmation' => [
	 *                              'title' => 'message (parsed as HTML)',
	 *                              'description' => 'optional message (parsed as HTML)'
	 *                         ]
	 *                     ]
	 *                 ]
	 *                 'prioritized' => (bool) true to request the link be placed outside the action menu.
	 *                                  false or omitted for the default behavior. By default, a link will
	 *                                  be placed inside the menu, unless there are maxPrioritizedActions
	 *                                  or fewer secondary links. If there are maxPrioritizedActions or
	 *                                  fewer secondary links, they will all appear outside the action menu.
	 *                                  At most maxPrioritizedActions links will be placed outside the action menu.
	 *                                  maxPrioritizedActions is 2 on desktop and 1 on mobile.
	 *                ...]
	 *
	 *               Note that you should call array_values(array_filter()) on the
	 *               result of this function (FIXME).
	 */
	public function getSecondaryLinks() {
		return [];
	}

	/**
	 * Get the ID of the associated event
	 * @return int Event id
	 */
	public function getEventId() {
		return $this->event->getId();
	}

	/**
	 * @return array
	 * @throws TimestampException
	 */
	public function jsonSerialize(): array {
		$body = $this->getBodyMessage();

		return [
			'header' => $this->getHeaderMessage()->parse(),
			'compactHeader' => $this->getCompactHeaderMessage()->parse(),
			'body' => $body ? $body->escaped() : '',
			'icon' => $this->getIconType(),
			'links' => [
				'primary' => $this->getPrimaryLinkWithMarkAsRead() ?: [],
				'secondary' => array_values( array_filter( $this->getSecondaryLinks() ) ),
			],
		];
	}

	/**
	 * @param User $user
	 * @return string
	 */
	protected function getTruncatedUsername( User $user ) {
		return $this->language->embedBidi( $this->language->truncateForVisual(
			$user->getName(), self::USERNAME_RECOMMENDED_LENGTH, '...', false ) );
	}

	/**
	 * @param Title $title
	 * @param bool $includeNamespace
	 * @return string
	 */
	protected function getTruncatedTitleText( Title $title, $includeNamespace = false ) {
		$text = $includeNamespace ? $title->getPrefixedText() : $title->getText();
		return $this->language->embedBidi( $this->language->truncateForVisual(
			$text, self::PAGE_NAME_RECOMMENDED_LENGTH, '...', false ) );
	}

	/**
	 * @param User|null $user
	 * @return array|null
	 */
	final protected function getUserLink( $user ) {
		if ( !$user ) {
			return null;
		}

		if ( !$this->userCan( RevisionRecord::DELETED_USER ) ) {
			return null;
		}

		$url = !$user->isRegistered()
			? SpecialPage::getTitleFor( 'Contributions', $user->getName() )->getFullURL()
			: $user->getUserPage()->getFullURL();

		$label = $user->getName();
		$truncatedLabel = $this->language->truncateForVisual(
			$label, self::USERNAME_AS_LABEL_RECOMMENDED_LENGTH, '...', false );
		$isTruncated = $label !== $truncatedLabel;

		return [
			'url' => $url,
			'label' => $this->language->embedBidi( $truncatedLabel ),
			'tooltip' => $isTruncated ? $label : '',
			'description' => '',
			'icon' => $user->isTemp() ? 'userTemporary' : 'userAvatar',
			'prioritized' => true,
		];
	}

	/**
	 * @param Title $title
	 * @param string $description
	 * @param bool $prioritized
	 * @param array $query
	 * @return array
	 */
	final protected function getPageLink( Title $title, $description, $prioritized, $query = [] ) {
		if ( $title->getNamespace() === NS_USER_TALK ) {
			$icon = 'userSpeechBubble';
		} elseif ( $title->isTalkPage() ) {
			$icon = 'speechBubbles';
		} else {
			$icon = 'article';
		}

		return [
			'url' => $title->getFullURL( $query ),
			'label' => $this->language->embedBidi(
				$this->language->truncateForVisual(
					$title->getText(), self::PAGE_NAME_AS_LABEL_RECOMMENDED_LENGTH, '...', false )
			),
			'tooltip' => $title->getPrefixedText(),
			'description' => $description,
			'icon' => $icon,
			'prioritized' => $prioritized,
		];
	}

	/**
	 * Get a dynamic action link
	 *
	 * @param Title $title Title relating to this action
	 * @param string|false $icon Optional. Symbolic name of the OOUI icon to use
	 * @param string $label link text (non-escaped)
	 * @param string|null $description descriptive text (optional, non-escaped)
	 * @param array $data Action data
	 * @param array $query
	 * @return array Array compatible with the structure of
	 *  secondary links
	 */
	final protected function getDynamicActionLink(
		Title $title,
		$icon,
		$label,
		$description = null,
		$data = [],
		$query = []
	) {
		if ( !$icon && $title->getNamespace() === NS_USER_TALK ) {
			$icon = 'userSpeechBubble';
		} elseif ( !$icon && $title->isTalkPage() ) {
			$icon = 'speechBubbles';
		} elseif ( !$icon ) {
			$icon = 'article';
		}

		return [
			'type' => 'dynamic-action',
			'label' => $label,
			'description' => $description,
			'data' => $data,
			'url' => $title->getFullURL( $query ),
			'icon' => $icon,
		];
	}

	/**
	 * Get an 'watch' or 'unwatch' dynamic action link
	 *
	 * @param Title $title Title to watch or unwatch
	 * @return array Array compatible with dynamic action link
	 */
	final protected function getWatchActionLink( Title $title ) {
		$isTitleWatched = MediaWikiServices::getInstance()->getWatchlistManager()
			->isWatched( $this->getUser(), $title );
		$availableAction = $isTitleWatched ? 'unwatch' : 'watch';

		$data = [
			'tokenType' => 'watch',
			'params' => [
				'action' => 'watch',
				'titles' => $title->getPrefixedText(),
			],
			'messages' => [
				'confirmation' => [
					// notification-dynamic-actions-watch-confirmation
					// notification-dynamic-actions-unwatch-confirmation
					'title' => $this
						->msg( 'notification-dynamic-actions-' . $availableAction . '-confirmation' )
						->params(
							$this->getTruncatedTitleText( $title ),
							$title->getFullURL(),
							$this->getUser()->getName()
						),
					// notification-dynamic-actions-watch-confirmation-description
					// notification-dynamic-actions-unwatch-confirmation-description
					'description' => $this
						->msg( 'notification-dynamic-actions-' . $availableAction . '-confirmation-description' )
						->params(
							$this->getTruncatedTitleText( $title ),
							$title->getFullURL(),
							$this->getUser()->getName()
						),
				],
			],
		];

		// "Unwatching" action requires another parameter
		if ( $isTitleWatched ) {
			$data[ 'params' ][ 'unwatch' ] = 1;
		}

		return $this->getDynamicActionLink(
			$title,
			// Design requirements are to flip the star icons
			// in their meaning; that is, for the 'unwatch' action
			// we should display an empty star, and for the 'watch'
			// action a full star. In OOUI icons, their names
			// are reversed.
			$isTitleWatched ? 'star' : 'unStar',
			// notification-dynamic-actions-watch
			// notification-dynamic-actions-unwatch
			$this->msg( 'notification-dynamic-actions-' . $availableAction )
				->params(
					$this->getTruncatedTitleText( $title ),
					$title->getFullURL( [ 'action' => $availableAction ] ),
					$this->getUser()->getName()
				)->text(),
			null,
			$data,
			[ 'action' => $availableAction ]
		);
	}
}

class_alias( EchoEventPresentationModel::class, 'EchoEventPresentationModel' );
