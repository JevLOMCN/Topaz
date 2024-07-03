<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace MediaWiki\Minerva\Menu\Entries;

use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;

/**
 * Note this is used by Extension:GrowthExperiments
 */
final class ProfileMenuEntry implements IProfileMenuEntry {
	/**
	 * @var UserIdentity
	 */
	private $user;

	/**
	 * Code used to track clicks on the link to profile page
	 * @var string|null
	 */
	private $profileTrackingCode = null;

	/**
	 * Custom profile URL, can be used to override where the profile link href
	 * @var string|null
	 */
	private $customProfileURL = null;

	/**
	 * Custom profile label, can be used to override the profile label
	 * @var string|null
	 */
	private $customProfileLabel = null;

	/**
	 * @param UserIdentity $user Currently logged in user/anon
	 */
	public function __construct( UserIdentity $user ) {
		$this->user = $user;
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return 'profile';
	}

	/**
	 * @inheritDoc
	 */
	public function overrideProfileURL( $customURL, $customLabel = null, $trackingCode = null ) {
		$this->customProfileURL = $customURL;
		$this->customProfileLabel = $customLabel;
		$this->profileTrackingCode = $trackingCode;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function getCSSClasses(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getComponents(): array {
		$username = $this->user->getName();
		return [ [
			'data-icon' => [
				'icon' => 'userAvatar-base20',
			],
			'label' => $this->customProfileLabel ?? $username,
			'array-attributes' => [
				[
					'key' => 'href',
					'value' => $this->customProfileURL ?? Title::makeTitle( NS_USER, $username )->getLocalURL(),
				],
				[
					'key' => 'data-event-name',
					'value' => 'menu.' . (
						$this->profileTrackingCode ?? self::DEFAULT_PROFILE_TRACKING_CODE
					)
				],
			],
			'classes' => 'menu__item--user',
		] ];
	}
}
