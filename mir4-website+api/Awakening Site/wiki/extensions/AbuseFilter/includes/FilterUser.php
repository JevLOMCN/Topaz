<?php

namespace MediaWiki\Extension\AbuseFilter;

use MediaWiki\Permissions\Authority;
use MediaWiki\User\User;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserNameUtils;
use MessageLocalizer;
use Psr\Log\LoggerInterface;

class FilterUser {
	public const SERVICE_NAME = 'AbuseFilterFilterUser';

	private MessageLocalizer $messageLocalizer;
	private UserGroupManager $userGroupManager;
	private UserNameUtils $userNameUtils;
	private LoggerInterface $logger;

	/**
	 * @param MessageLocalizer $messageLocalizer
	 * @param UserGroupManager $userGroupManager
	 * @param UserNameUtils $userNameUtils
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		MessageLocalizer $messageLocalizer,
		UserGroupManager $userGroupManager,
		UserNameUtils $userNameUtils,
		LoggerInterface $logger
	) {
		$this->messageLocalizer = $messageLocalizer;
		$this->userGroupManager = $userGroupManager;
		$this->userNameUtils = $userNameUtils;
		$this->logger = $logger;
	}

	/**
	 * @return Authority
	 */
	public function getAuthority(): Authority {
		return $this->getUser();
	}

	/**
	 * @return UserIdentity
	 */
	public function getUserIdentity(): UserIdentity {
		return $this->getUser();
	}

	/**
	 * Compares the given $user to see if they are the same as the FilterUser.
	 *
	 * @return bool
	 */
	public function isSameUserAs( UserIdentity $user ): bool {
		// Checking the usernames are equal is enough, as this is what is done by
		// User::equals and UserIdentityValue::equals.
		return $user->getName() === $this->getFilterUserName();
	}

	/**
	 * @todo Stop using the User class when User::newSystemUser is refactored.
	 * @return User
	 */
	private function getUser(): User {
		$user = User::newSystemUser( $this->getFilterUserName(), [ 'steal' => true ] );
		'@phan-var User $user';

		// Promote user to 'sysop' so it doesn't look
		// like an unprivileged account is blocking users
		if ( !in_array( 'sysop', $this->userGroupManager->getUserGroups( $user ) ) ) {
			$this->userGroupManager->addUserToGroup( $user, 'sysop' );
		}

		return $user;
	}

	/**
	 * Gets the username for the FilterUser.
	 *
	 * @return string
	 */
	private function getFilterUserName(): string {
		$username = $this->messageLocalizer->msg( 'abusefilter-blocker' )->inContentLanguage()->text();
		if ( !$this->userNameUtils->getCanonical( $username ) ) {
			// User name is invalid. Don't throw because this is a system message, easy
			// to change and make wrong either by mistake or intentionally to break the site.
			$this->logger->warning(
				'The AbuseFilter user\'s name is invalid. Please change it in ' .
				'MediaWiki:abusefilter-blocker'
			);
			// Use the default name to avoid breaking other stuff. This should have no harm,
			// aside from blocks temporarily attributed to another user.
			// Don't use the database in case the English onwiki message is broken, T284364
			$username = $this->messageLocalizer->msg( 'abusefilter-blocker' )
				->inLanguage( 'en' )
				->useDatabase( false )
				->text();
		}
		return $username;
	}
}
