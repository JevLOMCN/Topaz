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
 * @since 1.35
 */

namespace MediaWiki\Skins\Vector\FeatureManagement;

use MediaWiki\MediaWikiServices;
use MediaWiki\Skins\Vector\ConfigHelper;
use MediaWiki\Skins\Vector\Constants;
use MediaWiki\Skins\Vector\FeatureManagement\Requirements\SimpleRequirement;
use RequestContext;
use Wikimedia\Assert\Assert;

/**
 * A simple feature manager.
 *
 * NOTE: This API hasn't settled. It may change at any time without warning. Please don't bind to
 * it unless you absolutely need to.
 *
 * @unstable
 *
 * @package MediaWiki\Skins\Vector\FeatureManagement
 * @internal
 * @final
 */
class FeatureManager {

	/**
	 * A map of feature name to the array of requirements (referenced by name). A feature is only
	 * considered enabled when all of its requirements are met.
	 *
	 * See FeatureManager::registerFeature for additional detail.
	 *
	 * @var Array<string,string[]>
	 */
	private $features = [];

	/**
	 * A map of requirement name to the Requirement instance that represents it.
	 *
	 * The names of requirements are assumed to be static for the lifetime of the request. Therefore
	 * we can use them to look up Requirement instances quickly.
	 *
	 * @var Array<string,Requirement>
	 */
	private $requirements = [];

	/**
	 * Register a feature and its requirements.
	 *
	 * Essentially, a "feature" is a friendly (hopefully) name for some component, however big or
	 * small, that has some requirements. A feature manager allows us to decouple the component's
	 * logic from its requirements, allowing them to vary independently. Moreover, the use of
	 * friendly names wherever possible allows us to define a common language with our non-technical
	 * colleagues.
	 *
	 * ```php
	 * $featureManager->registerFeature( 'featureA', 'requirementA' );
	 * ```
	 *
	 * defines the "featureA" feature, which is enabled when the "requirementA" requirement is met.
	 *
	 * ```php
	 * $featureManager->registerFeature( 'featureB', [ 'requirementA', 'requirementB' ] );
	 * ```
	 *
	 * defines the "featureB" feature, which is enabled when the "requirementA" and "requirementB"
	 * requirements are met. Note well that the feature is only enabled when _all_ requirements are
	 * met, i.e. the requirements are evaluated in order and logically `AND`ed together.
	 *
	 * @param string $feature The name of the feature
	 * @param string|array $requirements The feature's requirements. As above, you can define a
	 * feature that requires a single requirement via the shorthand
	 *
	 *  ```php
	 *  $featureManager->registerFeature( 'feature', 'requirementA' );
	 *  // Equivalent to $featureManager->registerFeature( 'feature', [ 'requirementA' ] );
	 *  ```
	 *
	 * @throws \LogicException If the feature is already registered
	 * @throws \Wikimedia\Assert\ParameterAssertionException If the feature's requirements aren't
	 *  the name of a single requirement or a list of requirements
	 * @throws \InvalidArgumentException If the feature references a requirement that isn't
	 *  registered
	 */
	public function registerFeature( string $feature, $requirements ) {
		//
		// Validation
		if ( array_key_exists( $feature, $this->features ) ) {
			throw new \LogicException( sprintf(
				'Feature "%s" is already registered.',
				$feature
			) );
		}

		Assert::parameterType( 'string|array', $requirements, 'requirements' );

		$requirements = (array)$requirements;

		Assert::parameterElementType( 'string', $requirements, 'requirements' );

		foreach ( $requirements as $name ) {
			if ( !array_key_exists( $name, $this->requirements ) ) {
				throw new \InvalidArgumentException( sprintf(
					'Feature "%s" references requirement "%s", which hasn\'t been registered',
					$feature,
					$name
				) );
			}
		}

		// Mutation
		$this->features[$feature] = $requirements;
	}

	/**
	 * Gets user's preference value
	 *
	 * If user preference is not set or did not appear in config
	 * set it to default value we go back to defualt suffix value
	 * that will ensure that the feature will be enabled when requirements are met
	 *
	 * @param string $preferenceKey User preference key
	 * @return string
	 */
	public function getUserPreferenceValue( $preferenceKey ) {
		$user = RequestContext::getMain()->getUser();
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		return $userOptionsLookup->getOption(
			$user,
			$preferenceKey
			// For client preferences, this should be the same as `preferenceKey`
			// in 'resources/skins.vector.js/clientPreferences.json'
		);
	}

	/**
	 * Return a list of classes that should be added to the body tag
	 *
	 * @return array
	 */
	public function getFeatureBodyClass() {
		return array_map( function ( $featureName ) {
			// switch to lower case and switch from camel case to hyphens
			$featureClass = ltrim( strtolower( preg_replace( '/[A-Z]([A-Z](?![a-z]))*/', '-$0', $featureName ) ), '-' );
			$prefix = 'vector-feature-' . $featureClass . '-';

			// some features (eg night mode) will require request context to determine status
			$context = RequestContext::getMain();
			$request = $context->getRequest();
			$config = $context->getConfig();
			$title = $context->getTitle();

			// Client side preferences
			switch ( $featureName ) {
				case CONSTANTS::FEATURE_FONT_SIZE:
					$suffixEnabled = 'clientpref-' . $this->getUserPreferenceValue( CONSTANTS::PREF_KEY_FONT_SIZE );
					$suffixDisabled = 'clientpref-0';
					break;
				case CONSTANTS::PREF_NIGHT_MODE:
					// if night mode is disabled for the page, add the disabled class instead and return early
					if ( ConfigHelper::shouldDisable( $config->get( 'VectorNightModeOptions' ), $request, $title ) ) {
						return 'skin-night-mode-disabled';
					}

					$valueRequest = $request->getText( 'vectornightmode' );
					// If night mode query string is used, hardcode pref value to the night mode value
					// NOTE: The query string parameter only works for logged in users.
					// IF you have set a cookie locally this will be overriden.
					$value = $valueRequest !== '' ? self::resolveNightModeQueryValue( $valueRequest ) :
						$this->getUserPreferenceValue( CONSTANTS::PREF_KEY_NIGHT_MODE );
					$suffixEnabled = 'clientpref-' . $value;
					$suffixDisabled = 'clientpref-day';
					// Must be hardcoded to 'skin-theme-' to be consistent with Minerva
					// So that editors can target the same class across skins
					$prefix = 'skin-theme-';
					break;
				case CONSTANTS::FEATURE_LIMITED_WIDTH:
				case CONSTANTS::FEATURE_TOC_PINNED:
					$suffixEnabled = 'clientpref-1';
					$suffixDisabled = 'clientpref-0';
					break;
				default:
					// FIXME: Eventually this should not be necessary.
					$suffixEnabled = 'enabled';
					$suffixDisabled = 'disabled';
					break;
			}
			return $this->isFeatureEnabled( $featureName ) ?
				$prefix . $suffixEnabled : $prefix . $suffixDisabled;
		}, array_keys( $this->features ) );
	}

	/**
	 * Gets whether the feature's requirements are met.
	 *
	 * @param string $feature
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If the feature isn't registered
	 */
	public function isFeatureEnabled( string $feature ): bool {
		if ( !array_key_exists( $feature, $this->features ) ) {
			throw new \InvalidArgumentException( "The feature \"{$feature}\" isn't registered." );
		}

		$requirements = $this->features[$feature];

		foreach ( $requirements as $name ) {
			if ( !$this->requirements[$name]->isMet() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Register a complex {@see Requirement}.
	 *
	 * A complex requirement is one that depends on object that may or may not be fully loaded
	 * while the application is booting, e.g. see `User::isSafeToLoad`.
	 *
	 * Such requirements are expected to be registered during a hook that is run early on in the
	 * application lifecycle, e.g. the `BeforePerformAction` and `APIBeforeMain` hooks.
	 *
	 * @param Requirement $requirement
	 *
	 * @throws \LogicException If the requirement has already been registered
	 */
	public function registerRequirement( Requirement $requirement ) {
		$name = $requirement->getName();

		if ( array_key_exists( $name, $this->requirements ) ) {
			throw new \LogicException( "The requirement \"{$name}\" is already registered." );
		}

		$this->requirements[$name] = $requirement;
	}

	/**
	 * Register a {@see SimpleRequirement}.
	 *
	 * A requirement is some condition of the application state that a feature requires to be true
	 * or false.
	 *
	 * @param string $name The name of the requirement
	 * @param bool $isMet Whether the requirement is met
	 *
	 * @throws \LogicException If the requirement has already been registered
	 */
	public function registerSimpleRequirement( string $name, bool $isMet ) {
		$this->registerRequirement( new SimpleRequirement( $name, $isMet ) );
	}

	/**
	 * Gets whether the requirement is met.
	 *
	 * @param string $name The name of the requirement
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If the requirement isn't registered
	 */
	public function isRequirementMet( string $name ): bool {
		if ( !array_key_exists( $name, $this->requirements ) ) {
			throw new \InvalidArgumentException( "Requirement \"{$name}\" isn't registered." );
		}

		return $this->requirements[$name]->isMet();
	}

	/**
	 * Converts "1", "2", and "0" to equivalent values.
	 *
	 * @return string
	 */
	private static function resolveNightModeQueryValue( string $value ) {
		switch ( $value ) {
			case 'day':
			case 'night':
			case 'os':
				return $value;
			case '1':
				return 'night';
			case '2':
				return 'os';
			default:
				return 'day';
		}
	}
}
