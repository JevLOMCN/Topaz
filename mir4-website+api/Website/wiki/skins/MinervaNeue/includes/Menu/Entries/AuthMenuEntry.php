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

use MediaWiki\User\UserIdentity;
use MessageLocalizer;

/**
 * Model for a menu entry that represents log-in / profile+logout pair of links
 */
final class AuthMenuEntry extends CompositeMenuEntry implements IProfileMenuEntry {
	/**
	 * @var ProfileMenuEntry
	 */
	private $profileMenuEntry;

	/**
	 * Initialize the Auth menu entry
	 *
	 * @param UserIdentity $user Currently logged in user/anon
	 * @param MessageLocalizer $messageLocalizer used for text translation
	 * @param array $authLinksQuery Mapping of URI query parameter names to values.
	 */
	public function __construct(
		UserIdentity $user, MessageLocalizer $messageLocalizer, array $authLinksQuery
	) {
		$this->profileMenuEntry = new ProfileMenuEntry( $user );
		$entries = $user->isRegistered()
			? [ $this->profileMenuEntry ]
			: [ new LogInMenuEntry( $messageLocalizer, $authLinksQuery ) ];
		parent::__construct( $entries );
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return 'auth';
	}

	/**
	 * @inheritDoc
	 */
	public function overrideProfileURL( $customURL, $customLabel = null, $trackingCode = null ) {
		$this->profileMenuEntry->overrideProfileURL( $customURL, $customLabel, $trackingCode );
		return $this;
	}
}
