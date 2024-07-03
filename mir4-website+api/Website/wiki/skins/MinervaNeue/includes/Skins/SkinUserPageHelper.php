<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Minerva\Skins;

use IContextSource;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserNameUtils;

class SkinUserPageHelper {
	/**
	 * @var UserFactory
	 */
	private UserFactory $userFactory;

	/**
	 * @var UserNameUtils
	 */
	private UserNameUtils $userNameUtils;

	/**
	 * @var IContextSource
	 */
	private IContextSource $context;

	/**
	 * @var Title|null
	 */
	private ?Title $title;

	/**
	 * @var bool
	 */
	private $fetchedData = false;

	/**
	 * @var User|null
	 */
	private $pageUser;

	/**
	 * @param UserFactory $userFactory
	 * @param UserNameUtils $userNameUtils
	 */
	public function __construct(
		UserFactory $userFactory,
		UserNameUtils $userNameUtils
	) {
		$this->userFactory = $userFactory;
		$this->userNameUtils = $userNameUtils;
	}

	/**
	 * @param IContextSource $context
	 * @return $this
	 */
	public function setContext( IContextSource $context ) {
		$this->context = $context;
		return $this;
	}

	/**
	 * @param Title|null $title
	 * @return $this
	 */
	public function setTitle( ?Title $title ) {
		$this->title = $title;
		return $this;
	}

	/**
	 * Fetch user data and store locally for performance improvement
	 * @return User|null
	 */
	private function fetchData() {
		if ( $this->fetchedData === false ) {
			if ( $this->title && $this->title->inNamespace( NS_USER ) && !$this->title->isSubpage() ) {
				$this->pageUser = $this->buildPageUserObject( $this->title );
			}
			$this->fetchedData = true;
		}
		return $this->pageUser;
	}

	/**
	 * Return new User object based on username or IP address.
	 * @param Title $title
	 * @return User|null
	 */
	private function buildPageUserObject( Title $title ) {
		$titleText = $title->getText();

		if ( $this->userNameUtils->isIP( $titleText ) ) {
			return $this->userFactory->newAnonymous( $titleText );
		}

		$user = $this->userFactory->newFromName( $titleText );
		if ( $user && $user->isRegistered() ) {
			return $user;
		}

		return null;
	}

	/**
	 * @return User|null
	 */
	public function getPageUser() {
		return $this->fetchData();
	}

	/**
	 * @return bool
	 */
	public function isUserPage() {
		return $this->fetchData() !== null;
	}

	/**
	 * @return bool
	 */
	public function isUserPageAccessibleToCurrentUser() {
		$pageUser = $this->fetchData();
		$isHidden = $pageUser && $pageUser->isHidden();
		$canViewHidden = $this->context && $this->context->getAuthority()->isAllowed( 'hideuser' );
		return !$isHidden || $canViewHidden;
	}
}
