<?php

namespace MediaWiki\Extension\Notifications;

use MediaWiki\Title\Title;
use MediaWiki\User\User;

class SummaryParser {
	/** @var callable */
	private $userLookup;

	/**
	 * @param callable|null $userLookup Function that receives User object and returns its id
	 *  or 0 if the user doesn't exist. Passing null to this parameter will result in default
	 *  implementation being used.
	 */
	public function __construct( callable $userLookup = null ) {
		$this->userLookup = $userLookup;
		if ( !$this->userLookup ) {
			$this->userLookup = static function ( User $user ) {
				return $user->getId();
			};
		}
	}

	/**
	 * Returns a list of registered users linked in an edit summary
	 *
	 * @param string $summary
	 * @return User[] Array of username => User object
	 */
	public function parse( $summary ) {
		// Remove section autocomments. Replace with characters that can't be in titles,
		// to prevent fun stuff like "[[foo /* section */ bar]]".
		$summary = preg_replace( '#/\*.*?\*/#', ' [] ', $summary );

		$users = [];
		$regex = '/\[\[([' . Title::legalChars() . ']++)(?:\|.*?)?\]\]/';
		if ( preg_match_all( $regex, $summary, $matches ) ) {
			foreach ( $matches[1] as $match ) {
				if ( $match[0] === ':' ) {
					continue;
				}

				$title = Title::newFromText( $match );
				if ( $title
					 && $title->isLocal()
					 && $title->getNamespace() === NS_USER
				) {
					$user = User::newFromName( $title->getText() );
					$lookup = $this->userLookup;
					if ( $user && $lookup( $user ) > 0 ) {
						$users[$user->getName()] = $user;
					}
				}
			}
		}

		return $users;
	}
}
