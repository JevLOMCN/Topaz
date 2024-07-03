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

use ExtensionRegistry;
use MediaWiki\Config\Config;
use MediaWiki\Extension\BetaFeatures\BetaFeatures;
use MediaWiki\Request\WebRequest;
use MediaWiki\Skins\Vector\Constants;
use MediaWiki\Skins\Vector\FeatureManagement\Requirement;
use MediaWiki\User\UserIdentity;

/**
 * The `OverridableConfigRequirement` allows us to define requirements that can override
 * configuration like querystring parameters, e.g.
 *
 * ```lang=php
 * $featureManager->registerRequirement(
 *   new OverridableConfigRequirement(
 *     $config,
 *     $user,
 *     $request,
 *     MainConfigNames::Sitename,
 *     'requirementName',
 *     'overrideName',
 *     'configTestName',
 *   )
 * );
 * ```
 *
 * registers a requirement that will evaluate to true only when `mediawiki/includes/Setup.php` has
 * finished executing (after all service wiring has executed). I.e., every call to
 * `Requirement->isMet()` re-interrogates the request, user authentication status,
 * and config object for the current state and returns it. Contrast to:
 *
 * ```lang=php
 * $featureManager->registerSimpleRequirement(
 *   'requirementName',
 *   (bool)$config->get( MainConfigNames::Sitename )
 * );
 * ```
 *
 * wherein state is evaluated only once at registration time and permanently cached.
 *
 * NOTE: This API hasn't settled. It may change at any time without warning. Please don't bind to
 * it unless you absolutely need to
 *
 * @package MediaWiki\Skins\Vector\FeatureManagement\Requirements
 */
class OverridableConfigRequirement implements Requirement {

	private Config $config;

	private UserIdentity $user;

	private string $configName;

	private string $requirementName;

	private OverrideableRequirementHelper $helper;

	/**
	 * This constructor accepts all dependencies needed to determine whether
	 * the overridable config is enabled for the current user and request.
	 *
	 * @param Config $config
	 * @param UserIdentity $user
	 * @param WebRequest $request
	 * @param string $configName Any `Config` key. This name is used to query `$config` state.
	 * @param string $requirementName The name of the requirement presented to FeatureManager.
	 */
	public function __construct(
		Config $config,
		UserIdentity $user,
		WebRequest $request,
		string $configName,
		string $requirementName
	) {
		$this->config = $config;
		$this->user = $user;
		$this->configName = $configName;
		$this->requirementName = $requirementName;
		$this->helper = new OverrideableRequirementHelper( $request, $requirementName );
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->requirementName;
	}

	/**
	 * Check query parameter to override config or not.
	 * Then check for AB test value.
	 * Fallback to config value.
	 *
	 * @inheritDoc
	 */
	public function isMet(): bool {
		$isMet = $this->helper->isMet();
		if ( $isMet !== null ) {
			return $isMet;
		}

		// If AB test is not enabled, fallback to checking config state.
		$thisConfig = $this->config->get( $this->configName );

		// Backwards compatibility with config variables that have been set in production.
		if ( is_bool( $thisConfig ) ) {
			$thisConfig = [
				'logged_in' => $thisConfig,
				'logged_out' => $thisConfig,
				'beta' => $thisConfig,
			];
		} elseif ( array_key_exists( 'default', $thisConfig ) ) {
			$thisConfig = [
				'default' => $thisConfig['default'],
			];
		} else {
			$thisConfig = [
				'logged_in' => $thisConfig['logged_in'] ?? false,
				'logged_out' => $thisConfig['logged_out'] ?? false,
				'beta' => $thisConfig['beta'] ?? false,
			];
		}

		// Fallback to config.
		$userConfig = array_key_exists( 'default', $thisConfig ) ?
			$thisConfig[ 'default' ] :
			$thisConfig[ $this->user->isRegistered() ? 'logged_in' : 'logged_out' ];
		// Check if use has enabled beta features
		$betaFeatureConfig = array_key_exists( 'beta', $thisConfig ) && $thisConfig[ 'beta' ];
		$betaFeatureEnabled = in_array( $this->configName, Constants::VECTOR_BETA_FEATURES ) &&
			$betaFeatureConfig && $this->isVector2022BetaFeatureEnabled();
		// If user has enabled beta features, use beta config
		return $betaFeatureEnabled ? $betaFeatureConfig : $userConfig;
	}

	/**
	 * Check if user has enabled the Vector 2022 beta features
	 * @return bool
	 */
	public function isVector2022BetaFeatureEnabled(): bool {
		return ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' ) &&
			/* @phan-suppress-next-line PhanUndeclaredClassMethod */
			BetaFeatures::isFeatureEnabled(
			$this->user,
			Constants::VECTOR_2022_BETA_KEY
		);
	}
}
