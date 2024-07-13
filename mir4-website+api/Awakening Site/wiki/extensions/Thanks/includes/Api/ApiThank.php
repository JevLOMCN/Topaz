<?php

namespace MediaWiki\Extension\Thanks\Api;

use ApiBase;
use ApiMain;
use MediaWiki\Extension\Thanks\Storage\LogStore;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

/**
 * Base API module for Thanks
 *
 * @ingroup API
 * @ingroup Extensions
 */
abstract class ApiThank extends ApiBase {

	protected PermissionManager $permissionManager;
	protected LogStore $storage;

	public function __construct(
		ApiMain $main,
		$action,
		PermissionManager $permissionManager,
		LogStore $storage
	) {
		parent::__construct( $main, $action );
		$this->permissionManager = $permissionManager;
		$this->storage = $storage;
	}

	protected function dieOnBadUser( User $user ) {
		if ( !$user->isNamed() ) {
			$this->dieWithError( 'thanks-error-notloggedin', 'notloggedin' );
		} elseif ( $user->pingLimiter( 'thanks-notification' ) ) {
			$this->dieWithError( [ 'thanks-error-ratelimited', $user->getName() ], 'ratelimited' );
		}
	}

	/**
	 * Check whether the user is blocked from this title. (This is not the same
	 * as checking whether they are sitewide blocked, because a sitewide blocked
	 * user may still be allowed to thank on their own talk page.)
	 *
	 * This is separate from dieOnBadUser because we need to know the title.
	 *
	 * @param User $user
	 * @param Title $title
	 */
	protected function dieOnUserBlockedFromTitle( User $user, Title $title ) {
		if ( $this->permissionManager->isBlockedFrom( $user, $title ) ) {
			// Block should definitely exist
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->dieBlocked( $user->getBlock() );
		}
	}

	/**
	 * Check whether the user is sitewide blocked.
	 *
	 * This is separate from dieOnUserBlockedFromTitle because we need to know if the thank
	 * is related to a revision. (If it is, then use dieOnUserBlockedFromTitle instead.)
	 *
	 * @param User $user
	 */
	protected function dieOnUserBlockedFromThanks( User $user ) {
		$block = $user->getBlock();
		if (
			$block &&
			( $block->isSitewide() || $block->appliesToRight( 'thanks' ) )
		) {
			$this->dieBlocked( $block );
		}
	}

	protected function dieOnBadRecipient( User $user, User $recipient ) {
		if ( $user->getId() === $recipient->getId() ) {
			$this->dieWithError( 'thanks-error-invalidrecipient-self', 'invalidrecipient' );
		} elseif ( !$this->getConfig()->get( 'ThanksSendToBots' ) && $recipient->isBot() ) {
			$this->dieWithError( 'thanks-error-invalidrecipient-bot', 'invalidrecipient' );
		}
	}

	protected function markResultSuccess( $recipientName ) {
		$this->getResult()->addValue( null, 'result', [
			'success' => 1,
			'recipient' => $recipientName,
		] );
	}

	protected function haveAlreadyThanked( User $thanker, $uniqueId ) {
		return $this->storage->haveThanked( $thanker, $uniqueId );
	}

	protected function logThanks( User $user, User $recipient, $uniqueId ) {
		$this->storage->thank( $user, $recipient, $uniqueId );
	}

	public function needsToken() {
		return 'csrf';
	}

	public function isWriteMode() {
		// Writes to the Echo database and sometimes log tables.
		return true;
	}
}
