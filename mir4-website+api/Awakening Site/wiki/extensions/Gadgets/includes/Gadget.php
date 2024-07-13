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

namespace MediaWiki\Extension\Gadgets;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\Authority;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\User\UserIdentity;
use Skin;

/**
 * Represents one gadget definition.
 *
 * @copyright 2007 Daniel Kinzler
 */
class Gadget {
	/**
	 * Increment this when changing class structure
	 */
	public const GADGET_CLASS_VERSION = 18;

	public const CACHE_TTL = 86400;

	/** @var string[] */
	private $dependencies = [];
	/** @var string[] */
	private array $pages = [];
	/** @var string[] */
	private $peers = [];
	/** @var string[] */
	private $messages = [];
	/** @var string|null */
	private $name;
	/** @var string|null */
	private $definition = null;
	/** @var bool */
	private bool $resourceLoaded = false;
	/** @var bool */
	private bool $requiresES6 = false;
	/** @var string[] */
	private array $requiredRights = [];
	/** @var string[] */
	private array $requiredActions = [];
	/** @var string[] */
	private array $requiredSkins = [];
	/** @var int[]|string[] */
	private array $requiredNamespaces = [];
	/** @var string[] */
	private array $requiredCategories = [];
	/** @var string[] */
	private array $requiredContentModels = [];
	/** @var bool */
	private $onByDefault = false;
	/** @var bool */
	private $hidden = false;
	/** @var bool */
	private $package = false;
	/** @var string */
	private $type = '';
	/** @var string */
	private string $category = '';
	/** @var bool */
	private $supportsUrlLoad = false;

	public function __construct( array $options ) {
		foreach ( $options as $member => $option ) {
			switch ( $member ) {
				case 'category':
				case 'definition':
				case 'dependencies':
				case 'hidden':
				case 'messages':
				case 'name':
				case 'onByDefault':
				case 'package':
				case 'pages':
				case 'peers':
				case 'requiredActions':
				case 'requiredCategories':
				case 'requiredContentModels':
				case 'requiredNamespaces':
				case 'requiredRights':
				case 'requiredSkins':
				case 'requiresES6':
				case 'resourceLoaded':
				case 'supportsUrlLoad':
				case 'type':
					$this->{$member} = $option;
					break;
				default:
					throw new InvalidArgumentException( "Unrecognized '$member' parameter" );
			}
		}
	}

	/**
	 * Create a serialized array based on the metadata in a GadgetDefinitionContent object,
	 * from which a Gadget object can be constructed.
	 *
	 * @param string $id
	 * @param array $data
	 * @return array
	 */
	public static function serializeDefinition( string $id, array $data ): array {
		$prefixGadgetNs = static function ( $page ) {
			return GadgetRepo::RESOURCE_TITLE_PREFIX . $page;
		};
		return [
			'category' => $data['settings']['category'],
			'dependencies' => $data['module']['dependencies'],
			'hidden' => $data['settings']['hidden'],
			'messages' => $data['module']['messages'],
			'name' => $id,
			'onByDefault' => $data['settings']['default'],
			'package' => $data['settings']['package'],
			'pages' => array_map( $prefixGadgetNs, $data['module']['pages'] ),
			'peers' => $data['module']['peers'],
			'requiredActions' => $data['settings']['actions'],
			'requiredCategories' => $data['settings']['categories'],
			'requiredContentModels' => $data['settings']['contentModels'],
			'requiredNamespaces' => $data['settings']['namespaces'],
			'requiredRights' => $data['settings']['rights'],
			'requiredSkins' => $data['settings']['skins'],
			'requiresES6' => $data['settings']['requiresES6'],
			'resourceLoaded' => true,
			'supportsUrlLoad' => $data['settings']['supportsUrlLoad'],
			'type' => $data['module']['type'],
		];
	}

	/**
	 * Serialize to an array
	 * @return array
	 */
	public function toArray(): array {
		return [
			'category' => $this->category,
			'dependencies' => $this->dependencies,
			'hidden' => $this->hidden,
			'messages' => $this->messages,
			'name' => $this->name,
			'onByDefault' => $this->onByDefault,
			'package' => $this->package,
			'pages' => $this->pages,
			'peers' => $this->peers,
			'requiredActions' => $this->requiredActions,
			'requiredCategories' => $this->requiredCategories,
			'requiredContentModels' => $this->requiredContentModels,
			'requiredNamespaces' => $this->requiredNamespaces,
			'requiredRights' => $this->requiredRights,
			'requiredSkins' => $this->requiredSkins,
			'requiresES6' => $this->requiresES6,
			'resourceLoaded' => $this->resourceLoaded,
			'supportsUrlLoad' => $this->supportsUrlLoad,
			'type' => $this->type,
			// Legacy  (specific to MediaWikiGadgetsDefinitionRepo)
			'definition' => $this->definition,
		];
	}

	/**
	 * Get a placeholder object to use if a gadget doesn't exist
	 *
	 * @param string $id name
	 * @return Gadget
	 */
	public static function newEmptyGadget( $id ) {
		return new self( [ 'name' => $id ] );
	}

	/**
	 * Whether the provided gadget id is valid
	 *
	 * @param string $id
	 * @return bool
	 */
	public static function isValidGadgetID( $id ) {
		return $id !== '' && ResourceLoader::isValidModuleName( self::getModuleName( $id ) );
	}

	/**
	 * @return string Gadget name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string Message key
	 */
	public function getDescriptionMessageKey() {
		return 'gadget-' . $this->name;
	}

	/**
	 * @return string Gadget description parsed into HTML
	 */
	public function getDescription() {
		return wfMessage( $this->getDescriptionMessageKey() )->parse();
	}

	/**
	 * @return string Wikitext of gadget description
	 */
	public function getRawDescription() {
		return wfMessage( $this->getDescriptionMessageKey() )->plain();
	}

	/**
	 * @return string Name of category (aka section) our gadget belongs to. Empty string if none.
	 */
	public function getCategory(): string {
		return $this->category;
	}

	/**
	 * @param string $id Name of gadget
	 * @return string Name of ResourceLoader module for the gadget
	 */
	public static function getModuleName( $id ) {
		return "ext.gadget.{$id}";
	}

	/**
	 * Checks whether this gadget is enabled for given user
	 *
	 * @param UserIdentity $user user to check against
	 * @return bool
	 */
	public function isEnabled( UserIdentity $user ) {
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		return (bool)$userOptionsLookup->getOption( $user, "gadget-{$this->name}", $this->onByDefault );
	}

	/**
	 * Checks whether a given user may enable this gadget
	 *
	 * @param Authority $user The user to check against
	 * @return bool
	 */
	public function isAllowed( Authority $user ) {
		return !$this->requiredRights || $user->isAllowedAll( ...$this->requiredRights );
	}

	/**
	 * @return bool Whether this gadget is on by default for everyone
	 *  (but can be disabled in preferences)
	 */
	public function isOnByDefault() {
		return $this->onByDefault;
	}

	/**
	 * @return bool
	 */
	public function isHidden() {
		return $this->hidden;
	}

	/**
	 * @return bool
	 */
	public function isPackaged(): bool {
		// A packaged gadget needs to have a main script, so there must be at least one script
		return $this->package && $this->supportsResourceLoader() && $this->getScripts() !== [];
	}

	/**
	 * Whether to load the gadget on a given page action.
	 *
	 * @param string $action Action name
	 * @return bool
	 */
	public function isActionSupported( string $action ): bool {
		if ( count( $this->requiredActions ) === 0 ) {
			return true;
		}
		// Don't require specifying 'submit' action in addition to 'edit'
		if ( $action === 'submit' ) {
			$action = 'edit';
		}
		return in_array( $action, $this->requiredActions, true );
	}

	/**
	 * Whether to load the gadget on pages in a given namespace ID.
	 *
	 * @param int $namespace Namespace ID
	 * @return bool
	 */
	public function isNamespaceSupported( int $namespace ) {
		// This is intentionally a non-strict in_array() because
		// MediaWikiGadgetsDefinitionRepo sets numerical strings.
		return !$this->requiredNamespaces || in_array( $namespace, $this->requiredNamespaces );
	}

	/**
	 * Whether to load the gadget on pages in any of the given categories
	 *
	 * @param array $categories Category names (category title text, no namespace prefix, no dbkey-underscores)
	 * @return bool
	 */
	public function isCategorySupported( array $categories ) {
		if ( !$this->requiredCategories ) {
			return true;
		}
		foreach ( $categories as $category ) {
			if ( in_array( $category, $this->requiredCategories, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if this gadget is compatible with a skin
	 *
	 * @param Skin $skin
	 * @return bool
	 */
	public function isSkinSupported( Skin $skin ) {
		return !$this->requiredSkins || in_array( $skin->getSkinName(), $this->requiredSkins, true );
	}

	/**
	 * Check if this gadget is compatible with the given content model
	 *
	 * @param string $contentModel The content model ID
	 * @return bool
	 */
	public function isContentModelSupported( string $contentModel ) {
		return !$this->requiredContentModels || in_array( $contentModel, $this->requiredContentModels );
	}

	/**
	 * @return bool Whether the gadget can be loaded with `?withgadget` query parameter.
	 */
	public function supportsUrlLoad() {
		return $this->supportsUrlLoad;
	}

	/**
	 * @return bool Whether all of this gadget's JS components support ResourceLoader
	 */
	public function supportsResourceLoader() {
		return $this->resourceLoaded;
	}

	/**
	 * @return bool Whether this gadget requires ES6
	 */
	public function requiresES6(): bool {
		return $this->requiresES6 && !$this->onByDefault;
	}

	/**
	 * @return bool Whether this gadget has resources that can be loaded via ResourceLoader
	 */
	public function hasModule() {
		return $this->getStyles() || ( $this->supportsResourceLoader() && $this->getScripts() );
	}

	/**
	 * @return string|null Definition for this gadget from MediaWiki:Gadgets-definition,
	 *  or null if MediaWikiGadgetsJsonRepo is used.
	 */
	public function getDefinition() {
		return $this->definition;
	}

	/**
	 * @return string[] JS page names (including namespace)
	 */
	public function getScripts() {
		return array_values( array_filter( $this->pages, static function ( $page ) {
			return str_ends_with( $page, '.js' );
		} ) );
	}

	/**
	 * @return string[] CSS page names (including namespace)
	 */
	public function getStyles() {
		return array_values( array_filter( $this->pages, static function ( $page ) {
			return str_ends_with( $page, '.css' );
		} ) );
	}

	/**
	 * @return string[] JSON page names (including namespace)
	 */
	public function getJSONs(): array {
		return array_values( array_filter( $this->pages, static function ( $page ) {
			return str_ends_with( $page, '.json' );
		} ) );
	}

	/**
	 * @return string[] All page names for this gadget's resources
	 */
	public function getScriptsAndStyles() {
		return array_merge( $this->getScripts(), $this->getStyles(), $this->getJSONs() );
	}

	/**
	 * Returns list of scripts that don't support ResourceLoader
	 * @return string[]
	 */
	public function getLegacyScripts() {
		return $this->supportsResourceLoader() ? [] : $this->getScripts();
	}

	/**
	 * Returns names of resources this gadget depends on
	 * @return string[]
	 */
	public function getDependencies() {
		return $this->dependencies;
	}

	/**
	 * Get list of extra modules that should be loaded when this gadget is enabled
	 *
	 * Primary use case is to allow a Gadget that includes JavaScript to also load
	 * a (usually, hidden) styles-type module to be applied to the page. Dependencies
	 * don't work for this use case as those would not be part of page rendering.
	 *
	 * @return string[]
	 */
	public function getPeers() {
		return $this->peers;
	}

	/**
	 * @return string[]
	 */
	public function getMessages() {
		return $this->messages;
	}

	/**
	 * Get user rights required to enable this gadget
	 * @return string[]
	 */
	public function getRequiredRights() {
		return $this->requiredRights;
	}

	/**
	 * Get page actions on which the gadget loads
	 * @return string[]
	 */
	public function getRequiredActions() {
		return $this->requiredActions;
	}

	/**
	 * Get page namespaces in which this gadget loads
	 *
	 * Use isNamespaceSupported() instead for basic checks, as
	 * namespace IDs may be returned as numerical strings.
	 *
	 * Unknown namespaces and non-numerical values result in warnings
	 * on Special:Gadgets, via GadgetRepo::checkInvalidLoadConditions.
	 *
	 * @return int[]|string[]
	 */
	public function getRequiredNamespaces() {
		return $this->requiredNamespaces;
	}

	/**
	 * Returns categories in which this gadget loads
	 * @return string[]
	 */
	public function getRequiredCategories() {
		return $this->requiredCategories;
	}

	/**
	 * Get skins in which this gadget loads
	 * @return string[]
	 */
	public function getRequiredSkins() {
		return $this->requiredSkins;
	}

	/**
	 * Get page content models for which this gadget loads
	 * @return string[]
	 */
	public function getRequiredContentModels() {
		return $this->requiredContentModels;
	}

	/**
	 * Returns the load type of this Gadget's ResourceLoader module
	 * @return string 'styles' or 'general'
	 */
	public function getType() {
		if ( $this->type === 'styles' || $this->type === 'general' ) {
			return $this->type;
		}
		// Similar to ResourceLoaderWikiModule default
		if ( $this->getStyles() && !$this->getScripts() && !$this->dependencies ) {
			return 'styles';
		}

		return 'general';
	}

	/**
	 * Get validation warnings
	 * @return string[]
	 */
	public function getValidationWarnings(): array {
		$warnings = [];

		// Default gadget requiring ES6
		if ( $this->onByDefault && $this->requiresES6 ) {
			$warnings[] = "gadgets-validate-es6default";
		}

		// Gadget containing files with uncrecognised suffixes
		if ( count( array_diff( $this->pages, $this->getScriptsAndStyles() ) ) !== 0 ) {
			$warnings[] = "gadgets-validate-unknownpages";
		}

		// Non-package gadget containing JSON files
		if ( !$this->package && count( $this->getJSONs() ) > 0 ) {
			$warnings[] = "gadgets-validate-json";
		}

		// Package gadget without a script file in it (to serve as entry point)
		if ( $this->package && count( $this->getScripts() ) === 0 ) {
			$warnings[] = "gadgets-validate-noentrypoint";
		}

		// Gadget with type=styles having non-CSS files
		if ( $this->type === 'styles' && count( $this->getScripts() ) > 0 ) {
			$warnings[] = "gadgets-validate-scriptsnotallowed";
		}

		// Style-only gadgets having peers
		if ( $this->getType() === 'styles' && count( $this->peers ) > 0 ) {
			$warnings[] = "gadgets-validate-stylepeers";
		}

		return $warnings;
	}
}
