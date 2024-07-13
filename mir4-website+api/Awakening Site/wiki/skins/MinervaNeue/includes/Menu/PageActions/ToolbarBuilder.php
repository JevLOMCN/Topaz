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

namespace MediaWiki\Minerva\Menu\PageActions;

use ExtensionRegistry;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\IContextSource;
use MediaWiki\Minerva\LanguagesHelper;
use MediaWiki\Minerva\Menu\Entries\IMenuEntry;
use MediaWiki\Minerva\Menu\Entries\LanguageSelectorEntry;
use MediaWiki\Minerva\Menu\Entries\SingleMenuEntry;
use MediaWiki\Minerva\Menu\Group;
use MediaWiki\Minerva\Permissions\IMinervaPagePermissions;
use MediaWiki\Minerva\SkinOptions;
use MediaWiki\Minerva\Skins\SkinUserPageHelper;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use MediaWiki\Watchlist\WatchlistManager;
use SpecialMobileHistory;

class ToolbarBuilder {

	/**
	 * @var User Currently logged in user
	 */
	private $user;
	/**
	 * @var Title Article title user is currently browsing
	 */
	private $title;

	private IContextSource $context;

	/**
	 * @var IMinervaPagePermissions
	 */
	private $permissions;

	/**
	 * @var SkinOptions
	 */
	private $skinOptions;

	/**
	 * @var SkinUserPageHelper
	 */
	private $relevantUserPageHelper;

	/**
	 * @var LanguagesHelper
	 */
	private $languagesHelper;

	/**
	 * @var bool Correlates to $wgWatchlistExpiry feature flag.
	 */
	private $watchlistExpiryEnabled;

	/**
	 * @var WatchlistManager
	 */
	private $watchlistManager;

	/**
	 * ServiceOptions needed.
	 */
	public const CONSTRUCTOR_OPTIONS = [
		'WatchlistExpiry',
	];

	/**
	 * Build Group containing icons for toolbar
	 * @param Title $title Article title user is currently browsing
	 * @param User $user Currently logged in user
	 * @param IContextSource $context
	 * @param IMinervaPagePermissions $permissions Minerva permissions system
	 * @param SkinOptions $skinOptions
	 * @param SkinUserPageHelper $relevantUserPageHelper User Page helper. The
	 * UserPageHelper passed should always be specific to the user page Title. If on a
	 * user talk page, UserPageHelper should be instantiated with the user page
	 * Title and NOT with the user talk page Title.
	 * @param LanguagesHelper $languagesHelper Helper to check title languages/variants
	 * @param ServiceOptions $options
	 * @param WatchlistManager $watchlistManager
	 */
	public function __construct(
		Title $title,
		User $user,
		IContextSource $context,
		IMinervaPagePermissions $permissions,
		SkinOptions $skinOptions,
		SkinUserPageHelper $relevantUserPageHelper,
		LanguagesHelper $languagesHelper,
		ServiceOptions $options,
		WatchlistManager $watchlistManager
	) {
		$this->title = $title;
		$this->user = $user;
		$this->context = $context;
		$this->permissions = $permissions;
		$this->skinOptions = $skinOptions;
		$this->relevantUserPageHelper = $relevantUserPageHelper;
		$this->languagesHelper = $languagesHelper;
		$this->watchlistExpiryEnabled = $options->get( 'WatchlistExpiry' );
		$this->watchlistManager = $watchlistManager;
	}

	/**
	 * @param array $actions
	 * @param array $views
	 * @return Group
	 */
	public function getGroup( array $actions, array $views ): Group {
		$group = new Group( 'p-views' );
		$permissions = $this->permissions;
		$userPageOrUserTalkPageWithOverflowMode = $this->skinOptions->get( SkinOptions::TOOLBAR_SUBMENU )
			&& $this->relevantUserPageHelper->isUserPage();

		if ( !$userPageOrUserTalkPageWithOverflowMode && $permissions->isAllowed(
			IMinervaPagePermissions::SWITCH_LANGUAGE ) ) {
			$group->insertEntry( new LanguageSelectorEntry(
				$this->title,
				$this->languagesHelper->doesTitleHasLanguagesOrVariants(
					$this->context->getOutput(),
					$this->title
				),
				$this->context,
				true
			) );
		}

		$watchKey = $key = isset( $actions['unwatch'] ) ? 'unwatch' : 'watch';
		// The watchstar is typically not shown to anonymous users but it is in Minerva.
		$watchData = $actions[ $watchKey ] ?? [
			'icon' => 'star',
			'class' => '',
			'href' => $this->getLoginUrl( [ 'returnto' => $this->title ] ),
			'text' => $this->context->msg( 'watch' ),
		];
		if ( $permissions->isAllowed( IMinervaPagePermissions::WATCHABLE ) && $watchData ) {
			$group->insertEntry( $this->createWatchPageAction( $watchKey, $watchData ) );
		}

		$historyView = $views[ 'history'] ?? [];
		if ( $historyView && $permissions->isAllowed( IMinervaPagePermissions::HISTORY ) ) {
			$group->insertEntry( $this->getHistoryPageAction( $historyView ) );
		}

		$isUserPage = $this->relevantUserPageHelper->isUserPage();
		$isUserPageAccessible = $this->relevantUserPageHelper->isUserPageAccessibleToCurrentUser();
		if ( $isUserPage && $isUserPageAccessible ) {
			// T235681: Contributions icon should be added to toolbar on user pages
			// and user talk pages for all users
			$user = $this->relevantUserPageHelper->getPageUser();
			$group->insertEntry( $this->createContributionsPageAction( $user ) );
		}

		// We want the edit icon/action(s) always to be the last element on the toolbar list
		if ( $permissions->isAllowed( IMinervaPagePermissions::CONTENT_EDIT ) ) {
			foreach ( $views as $key => $viewData ) {
				if ( in_array( $key, [ 've-edit', 'viewsource', 'edit' ] ) ) {
					$group->insertEntry( $this->createEditPageAction( $key, $viewData ) );
				}
			}
		}
		return $group;
	}

	/**
	 * Create Contributions page action visible on user pages or user talk pages
	 * for given $user
	 *
	 * @param UserIdentity $user Determines what the contribution page action will link to
	 * @return IMenuEntry
	 */
	protected function createContributionsPageAction( UserIdentity $user ): IMenuEntry {
		$label = $this->context->msg( 'mobile-frontend-user-page-contributions' );

		$entry = new SingleMenuEntry(
			'page-actions-contributions',
			$label->escaped(),
			SpecialPage::getTitleFor( 'Contributions', $user->getName() )->getLocalURL() );
		$entry->setTitle( $label )
			->trackClicks( 'contributions' )
			->setIcon( 'userContributions' );

		return $entry;
	}

	/**
	 * Creates the "edit" page action: the well-known pencil icon that, when tapped, will open an
	 * editor with the lead section loaded.
	 *
	 * @param string $key
	 * @param array $editAction
	 * @return IMenuEntry An edit page actions menu entry
	 */
	protected function createEditPageAction( string $key, array $editAction ): IMenuEntry {
		$title = $this->title;

		$id = $editAction['single-id'] ?? 'ca-edit';
		$entry = new SingleMenuEntry(
			'page-actions-' . $key,
			$editAction['text'],
			$editAction['href'],
			'edit-page'
		);
		$iconFallback = $key === 'viewsource' ? 'editLock' : 'edit';
		$icon = $editAction['icon'] ?? $iconFallback;
		$entry->setIcon( $icon . '-base20' )
			->trackClicks( $key )
			->setTitle( $this->context->msg( 'tooltip-' . $id ) )
			->setNodeID( $id );
		return $entry;
	}

	/**
	 * Creates the "watch" or "unwatch" action: the well-known star icon that, when tapped, will
	 * add the page to or remove the page from the user's watchlist; or, if the user is logged out,
	 * will direct the user's UA to Special:Login.
	 *
	 * @param string $watchKey either watch or unwatch
	 * @param array $watchData
	 * @return IMenuEntry An watch/unwatch page actions menu entry
	 */
	protected function createWatchPageAction( string $watchKey, array $watchData ): IMenuEntry {
		$entry = new SingleMenuEntry(
			'page-actions-watch',
			$watchData['text'],
			$watchData['href'],
			$watchData[ 'class' ],
			$this->permissions->isAllowed( IMinervaPagePermissions::WATCH )
		);
		$icon = $watchData['icon'] ?? '';
		if ( $icon ) {
			$icon .= $watchKey === 'unwatch' ? '-progressive' : '-base20';
		}
		return $entry->trackClicks( $watchKey )
			->setIcon( $icon )
			->setTitle( $this->context->msg( $watchKey ) )
			->setNodeID( 'ca-watch' );
	}

	/**
	 * Creates a history action: An icon that links to the mobile history page.
	 *
	 * @param array $historyAction
	 * @return IMenuEntry A menu entry object that represents a map of HTML attributes
	 * and a 'text' property to be used with the pageActionMenu.mustache template.
	 */
	protected function getHistoryPageAction( array $historyAction ): IMenuEntry {
		$entry = new SingleMenuEntry(
			'page-actions-history',
			$historyAction['text'],
			$historyAction['href'],
		);
		$icon = $historyAction['icon'] ?? 'history';
		$entry->setIcon( $icon . '-base20' )
			->trackClicks( 'history' );
		return $entry;
	}

	/**
	 * Get the URL for the history page for the given title using Special:History
	 * when available.
	 * FIXME: temporary duplicated code, same as SkinMinerva::getHistoryUrl()
	 * @param Title $title The Title object of the page being viewed
	 * @return string
	 */
	protected function getHistoryUrl( Title $title ) {
		return ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			   SpecialMobileHistory::shouldUseSpecialHistory( $title, $this->user ) ?
			SpecialPage::getTitleFor( 'History', $title )->getLocalURL() :
			$title->getLocalURL( [ 'action' => 'history' ] );
	}

	/**
	 * Prepares a url to the Special:UserLogin with query parameters
	 * @param array $query
	 * @return string
	 */
	private function getLoginUrl( $query ) {
		return SpecialPage::getTitleFor( 'Userlogin' )->getLocalURL( $query );
	}
}
