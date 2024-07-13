<?php

namespace MediaWiki\Extension\SpamBlacklist;

use LogicException;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Wikimedia\AtEase\AtEase;

/**
 * Email Blacklisting
 */
class EmailBlacklist extends BaseBlacklist {
	/**
	 * @inheritDoc
	 * @suppress PhanPluginNeverReturnMethod LSP/ISP violation
	 */
	public function filter( array $links, ?Title $title, User $user, $preventLog = false ) {
		throw new LogicException( __CLASS__ . ' cannot be used to filter links.' );
	}

	/**
	 * Returns the code for the blacklist implementation
	 *
	 * @return string
	 */
	protected function getBlacklistType() {
		return 'email';
	}

	/**
	 * Checks a User object for a blacklisted email address
	 *
	 * @param User $user
	 * @return bool True on valid email
	 */
	public function checkUser( User $user ) {
		$blacklists = $this->getBlacklists();
		$whitelists = $this->getWhitelists();

		// The email to check
		$email = $user->getEmail();

		if ( !count( $blacklists ) ) {
			// Nothing to check
			return true;
		}

		// Check for whitelisted email addresses
		if ( is_array( $whitelists ) ) {
			wfDebugLog( 'SpamBlacklist', "Excluding whitelisted email addresses from " .
				count( $whitelists ) . " regexes: " . implode( ', ', $whitelists ) . "\n" );
			foreach ( $whitelists as $regex ) {
				AtEase::suppressWarnings();
				$match = preg_match( $regex, $email );
				AtEase::restoreWarnings();
				if ( $match ) {
					// Whitelisted email
					return true;
				}
			}
		}

		# Do the match
		wfDebugLog( 'SpamBlacklist', "Checking e-mail address against " . count( $blacklists ) .
			" regexes: " . implode( ', ', $blacklists ) . "\n" );
		foreach ( $blacklists as $regex ) {
			AtEase::suppressWarnings();
			$match = preg_match( $regex, $email );
			AtEase::restoreWarnings();
			if ( $match ) {
				return false;
			}
		}

		return true;
	}
}
