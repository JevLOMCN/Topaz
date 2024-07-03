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

/**
 * The `OverridableConfigRequirement` allows us to define requirements that can override
 * requirements with querystring parameters.
 *
 * NOTE: This API hasn't settled. It may change at any time without warning. Please don't bind to
 * it unless you absolutely need to
 *
 * @package MediaWiki\Skins\Vector\FeatureManagement\Requirements
 */
class OverrideableRequirementHelper {
	private WebRequest $request;

	private string $requirementName;

	private string $overrideName;

	/**
	 * This constructor accepts all dependencies needed to determine whether
	 * the overridable config is enabled for the current user and request.
	 *
	 * @param WebRequest $request
	 * @param string $requirementName The name of the requirement presented to FeatureManager.
	 */
	public function __construct(
		WebRequest $request,
		string $requirementName
	) {
		$this->request = $request;
		$this->overrideName = 'vector' . strtolower( $requirementName );
		$this->requirementName = $requirementName;
	}

	/**
	 * Check query parameter to override config or not.
	 * Then check for AB test value.
	 * Fallback to config value.
	 *
	 * @return bool|null
	 */
	public function isMet(): ?bool {
		// Check query parameter.
		if ( $this->request->getCheck( $this->overrideName ) ) {
			return $this->request->getBool( $this->overrideName );
		}
		$vectorReq = 'Vector' . $this->requirementName;
		if ( $this->request->getCheck( $vectorReq ) ) {
			return $this->request->getBool( $vectorReq );
		}
		return null;
	}
}
