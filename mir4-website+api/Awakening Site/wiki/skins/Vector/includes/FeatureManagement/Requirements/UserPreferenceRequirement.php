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

namespace MediaWiki\Skins\Vector\FeatureManagement\Requirements;

use MediaWiki\Request\WebRequest;
use MediaWiki\Skins\Vector\FeatureManagement\Requirement;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserIdentity;

/**
 * @package MediaWiki\Skins\Vector\FeatureManagement\Requirements
 */
final class UserPreferenceRequirement implements Requirement {

	private UserIdentity $user;

	private UserOptionsLookup $userOptionsLookup;

	private string $optionName;

	private string $requirementName;

	private ?Title $title;

	private OverrideableRequirementHelper $helper;

	/**
	 * This constructor accepts all dependencies needed to determine whether
	 * the overridable config is enabled for the current user and request.
	 *
	 * @param UserIdentity $user
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param string $optionName The name of the user preference.
	 * @param string $requirementName The name of the requirement presented to FeatureManager.
	 * @param WebRequest $request
	 * @param Title|null $title
	 */
	public function __construct(
		UserIdentity $user,
		UserOptionsLookup $userOptionsLookup,
		string $optionName,
		string $requirementName,
		WebRequest $request,
		Title $title = null
	) {
		$this->user = $user;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->optionName = $optionName;
		$this->requirementName = $requirementName;
		$this->title = $title;
		$this->helper = new OverrideableRequirementHelper( $request, $requirementName );
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->requirementName;
	}

	/**
	 * Checks whether the user preference is enabled or not. Returns true if
	 * enabled AND title is not null.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public function isPreferenceEnabled(): bool {
		$user = $this->user;
		$userOptionsLookup = $this->userOptionsLookup;
		$optionValue = $userOptionsLookup->getOption(
			$user,
			$this->optionName
		);
		// Check for 0, '0' or 'disabled'.
		// Any other value will be handled as enabled.
		$isEnabled = $optionValue && $optionValue !== 'disabled';

		return $this->title && $isEnabled;
	}

	/**
	 * @inheritDoc
	 */
	public function isMet(): bool {
		$override = $this->helper->isMet();
		return $override ?? $this->isPreferenceEnabled();
	}
}
