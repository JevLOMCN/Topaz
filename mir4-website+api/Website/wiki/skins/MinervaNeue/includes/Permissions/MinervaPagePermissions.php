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
namespace MediaWiki\Minerva\Permissions;

use ContentHandler;
use IContextSource;
use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigException;
use MediaWiki\Content\IContentHandlerFactory;
use MediaWiki\MainConfigNames;
use MediaWiki\Minerva\LanguagesHelper;
use MediaWiki\Minerva\SkinOptions;
use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use MediaWiki\Watchlist\WatchlistManager;

/**
 * A wrapper for all available Minerva permissions.
 */
final class MinervaPagePermissions implements IMinervaPagePermissions {
	/**
	 * @var Title Current page title
	 */
	private $title;
	/**
	 * @var Config Extension config
	 */
	private $config;

	/**
	 * @var Authority
	 */
	private $performer;

	private OutputPage $out;

	/**
	 * @var ContentHandler
	 */
	private $contentHandler;

	/**
	 * @var SkinOptions Minerva skin options
	 */
	private $skinOptions;

	/**
	 * @var LanguagesHelper
	 */
	private $languagesHelper;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager;

	/**
	 * @var IContentHandlerFactory
	 */
	private $contentHandlerFactory;

	private UserFactory $userFactory;

	private WatchlistManager $watchlistManager;

	/**
	 * Initialize internal Minerva Permissions system
	 * @param SkinOptions $skinOptions
	 * @param LanguagesHelper $languagesHelper
	 * @param PermissionManager $permissionManager
	 * @param IContentHandlerFactory $contentHandlerFactory
	 * @param UserFactory $userFactory
	 * @param WatchlistManager $watchlistManager
	 */
	public function __construct(
		SkinOptions $skinOptions,
		LanguagesHelper $languagesHelper,
		PermissionManager $permissionManager,
		IContentHandlerFactory $contentHandlerFactory,
		UserFactory $userFactory,
		WatchlistManager $watchlistManager
	) {
		$this->skinOptions = $skinOptions;
		$this->languagesHelper = $languagesHelper;
		$this->permissionManager = $permissionManager;
		$this->contentHandlerFactory = $contentHandlerFactory;
		$this->userFactory = $userFactory;
		$this->watchlistManager = $watchlistManager;
	}

	/**
	 * @param IContextSource $context
	 * @return $this
	 */
	public function setContext( IContextSource $context ) {
		$this->title = $context->getTitle();
		$this->config = $context->getConfig();
		$this->performer = $context->getAuthority();
		$this->out = $context->getOutput();
		// Title may be undefined in certain contexts (T179833)
		// TODO: Check if this is still true if we always pass a context instead of using global one
		if ( $this->title ) {
			$this->contentHandler = $this->contentHandlerFactory->getContentHandler(
				$this->title->getContentModel()
			);
		}
		return $this;
	}

	/**
	 * Gets whether or not the action is allowed.
	 *
	 * Actions isn't allowed when:
	 * <ul>
	 *   <li>the user is on the main page</li>
	 * </ul>
	 *
	 * The "edit" action is not allowed if editing is not possible on the page
	 * @see method isCurrentPageContentModelEditable
	 *
	 * The "switch-language" is allowed if there are interlanguage links on the page,
	 * or <code>$wgMinervaAlwaysShowLanguageButton</code> is truthy.
	 *
	 * @inheritDoc
	 * @throws ConfigException
	 */
	public function isAllowed( $action ) {
		if ( !$this->title ) {
			return false;
		}

		// T206406: Enable "Talk" or "Discussion" button on Main page, also, not forgetting
		// the "switch-language" button. But disable "edit" and "watch" actions.
		if ( $this->title->isMainPage() ) {
			if ( $action === self::SWITCH_LANGUAGE ) {
				return !$this->config->get( MainConfigNames::HideInterlanguageLinks );
			}
			// Only the talk page is allowed on the main page provided user is registered.
			// talk page permission is disabled on mobile for anons
			// https://phabricator.wikimedia.org/T54165
			return $action === self::TALK && $this->performer->isRegistered();
		}

		if ( $action === self::TALK ) {
			return (
				$this->title->isTalkPage() ||
				$this->title->canHaveTalkPage()
			);
		}

		if ( $action === self::HISTORY && $this->title->exists() ) {
			return $this->skinOptions->get( SkinOptions::HISTORY_IN_PAGE_ACTIONS );
		}

		if ( $action === SkinOptions::TOOLBAR_SUBMENU ) {
			return $this->skinOptions->get( SkinOptions::TOOLBAR_SUBMENU );
		}

		if ( $action === self::EDIT_OR_CREATE ) {
			return $this->canEditOrCreate();
		}

		if ( $action === self::CONTENT_EDIT ) {
			return $this->isCurrentPageContentModelEditable();
		}

		if ( $action === self::WATCHABLE || $action === self::WATCH ) {
			$isWatchable = $this->watchlistManager->isWatchable( $this->title );

			if ( $action === self::WATCHABLE ) {
				return $isWatchable;
			}
			if ( $action === self::WATCH ) {
				return $isWatchable &&
					$this->performer->isAllowedAll( 'viewmywatchlist', 'editmywatchlist' );
			}
		}

		if ( $action === self::SWITCH_LANGUAGE ) {
			if ( $this->config->get( MainConfigNames::HideInterlanguageLinks ) ) {
				return false;
			}
			return $this->languagesHelper->doesTitleHasLanguagesOrVariants( $this->out, $this->title ) ||
				$this->config->get( 'MinervaAlwaysShowLanguageButton' );
		}

		if ( $action === self::MOVE ) {
			return $this->canMove();
		}

		if ( $action === self::DELETE ) {
			return $this->canDelete();
		}

		if ( $action === self::PROTECT ) {
			return $this->canProtect();
		}

		// Unknown action has been passed.
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function isTalkAllowed() {
		return $this->isAllowed( self::TALK );
	}

	/**
	 * Checks whether the editor can handle the existing content handler type.
	 *
	 * @return bool
	 */
	protected function isCurrentPageContentModelEditable() {
		if ( !$this->contentHandler ) {
			return false;
		}

		if (
			$this->contentHandler->supportsDirectEditing() &&
			$this->contentHandler->supportsDirectApiEditing()
		) {
			return true;
		}

		// For content types with custom action=edit handlers, let them do their thing
		if ( array_key_exists( 'edit', $this->contentHandler->getActionOverrides() ?? [] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns true if $title page exists and is editable or is creatable by $user as determined by
	 * quick checks.
	 * @return bool
	 */
	private function canEditOrCreate() {
		if ( !$this->title ) {
			return false;
		}

		$userQuickEditCheck =
			$this->performer->probablyCan( 'edit', $this->title ) && (
				$this->title->exists() ||
				$this->performer->probablyCan( 'create', $this->title )
			);
		if ( $this->performer->isRegistered() ) {
			$legacyUser = $this->userFactory->newFromAuthority( $this->performer );
			$blocked = $this->permissionManager->isBlockedFrom(
				$legacyUser, $this->title, true
			);
		} else {
			$blocked = false;
		}
		return $this->isCurrentPageContentModelEditable() && $userQuickEditCheck && !$blocked;
	}

	/**
	 * Checks whether the user has the permissions to move the current page.
	 *
	 * @return bool
	 */
	private function canMove() {
		if ( !$this->title ) {
			return false;
		}

		return $this->performer->probablyCan( 'move', $this->title )
			&& $this->title->exists();
	}

	/**
	 * Checks whether the user has the permissions to delete the current page.
	 *
	 * @return bool
	 */
	private function canDelete() {
		if ( !$this->title ) {
			return false;
		}

		return $this->performer->probablyCan( 'delete', $this->title )
			&& $this->title->exists();
	}

	/**
	 * Checks whether the user has the permissions to change the protections status of the current page.
	 *
	 * @return bool
	 */
	private function canProtect() {
		if ( !$this->title ) {
			return false;
		}

		return $this->performer->probablyCan( 'protect', $this->title )
			&& $this->title->exists();
	}
}
