<?php
namespace MediaWiki\Skins\Vector\Components;

use MediaWiki\Linker\Linker;
use MediaWiki\Skin\SkinComponentLink;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use Message;
use MessageLocalizer;

/**
 * VectorComponentUserLinks component
 */
class VectorComponentUserLinks implements VectorComponent {

	private const BUTTON_CLASSES = 'cdx-button cdx-button--fake-button '
		. 'cdx-button--fake-button--enabled cdx-button--weight-quiet';
	private const ICON_ONLY_BUTTON_CLASS = 'cdx-button--icon-only';

	/** @var MessageLocalizer */
	private $localizer;
	/** @var UserIdentity */
	private $user;
	/** @var array */
	private $portletData;
	/** @var array */
	private $linkOptions;
	/** @var string */
	private $userIcon;

	/**
	 * @param MessageLocalizer $localizer
	 * @param UserIdentity $user
	 * @param array $portletData
	 * @param array $linkOptions
	 * @param string $userIcon that represents the current type of user
	 */
	public function __construct(
		MessageLocalizer $localizer,
		UserIdentity $user,
		array $portletData,
		array $linkOptions,
		string $userIcon = 'userAvatar'
	) {
		$this->localizer = $localizer;
		$this->user = $user;
		$this->portletData = $portletData;
		$this->linkOptions = $linkOptions;
		$this->userIcon = $userIcon;
	}

	/**
	 * @param string $key
	 * @return Message
	 */
	private function msg( $key ): Message {
		return $this->localizer->msg( $key );
	}

	/**
	 * @param bool $isDefaultAnonUserLinks
	 * @param bool $isAnonEditorLinksEnabled
	 * @return VectorComponentDropdown
	 */
	private function getDropdown( $isDefaultAnonUserLinks, $isAnonEditorLinksEnabled ) {
		$user = $this->user;
		$isAnon = !$user->isRegistered();

		$class = 'vector-user-menu';
		$class .= ' vector-button-flush-right';
		$class .= !$isAnon ?
			' vector-user-menu-logged-in' :
			' vector-user-menu-logged-out';

		// Hide entire user links dropdown on larger viewports if it only contains
		// create account & login link, which are only shown on smaller viewports
		if ( $isAnon && $isDefaultAnonUserLinks && !$isAnonEditorLinksEnabled ) {
			$class .= ' user-links-collapsible-item';
		}

		$tooltip = '';
		$icon = $this->userIcon;
		if ( $icon === '' ) {
			$icon = 'ellipsis';
			// T287494 We use tooltip messages to provide title attributes on hover over certain menu icons.
			// For modern Vector, the "tooltip-p-personal" key is set to "User menu" which is appropriate for
			// the user icon (dropdown indicator for user links menu) for logged-in users.
			// This overrides the tooltip for the user links menu icon which is an ellipsis for anonymous users.
			$tooltip = Linker::tooltip( 'vector-anon-user-menu-title' ) ?? '';
		}

		return new VectorComponentDropdown(
			'vector-user-links-dropdown', $this->msg( 'personaltools' )->text(), $class, $icon, $tooltip
		);
	}

	/**
	 * @param bool $isDefaultAnonUserLinks
	 * @param bool $isAnonEditorLinksEnabled
	 * @return array
	 */
	private function getMenus( $isDefaultAnonUserLinks, $isAnonEditorLinksEnabled ) {
		$user = $this->user;
		$isAnon = !$user->isRegistered();
		$portletData = $this->portletData;

		// Hide default user menu on larger viewports if it only contains
		// create account & login link, which are only shown on smaller viewports
		// FIXME: Replace array_merge with an add class helper function
		$userMenuClass = $portletData[ 'data-user-menu' ][ 'class' ];
		$userMenuClass = $isAnon && $isDefaultAnonUserLinks ?
			$userMenuClass . ' user-links-collapsible-item' : $userMenuClass;
		$dropdownMenus = [
			new VectorComponentMenu( [
				'label' => null,
				'class' => $userMenuClass
			] + $portletData[ 'data-user-menu' ] )
		];

		if ( $isAnon ) {
			// T317789: The `anontalk` and `anoncontribs` links will not be added to
			// the menu if `$wgGroupPermissions['*']['edit']` === false which can
			// leave the menu empty due to our removal of other user menu items in
			// `Hooks::updateUserLinksDropdownItems`. In this case, we do not want
			// to render the anon "learn more" link.
			if ( $isAnonEditorLinksEnabled ) {
				$anonUserMenuData = $portletData[ 'data-user-menu-anon-editor' ];
				try {
					$anonEditorLabelLinkData = [
						'text' => $this->msg( 'vector-anon-user-menu-pages-learn' )->text(),
						'href' => Title::newFromTextThrow( $this->msg( 'vector-intro-page' )->text() )->getLocalURL(),
						'aria-label' => $this->msg( 'vector-anon-user-menu-pages-label' )->text(),
					];
					$anonEditorLabelLink = new SkinComponentLink(
						'', $anonEditorLabelLinkData, $this->localizer, $this->linkOptions
					);
					$anonEditorLabelLinkHtml = $anonEditorLabelLink->getTemplateData()[ 'html' ];
					$anonUserMenuData['html-label'] = $this->msg( 'vector-anon-user-menu-pages' )->escaped() .
						" " . $anonEditorLabelLinkHtml;
					$anonUserMenuData['label'] = null;
				} catch ( MalformedTitleException $e ) {
					// ignore (T340220)
				}
				$dropdownMenus[] = new VectorComponentMenu( $anonUserMenuData );
			}
		} else {
			// Logout isn't enabled for temp users, who are considered still considered registered
			$isLogoutLinkEnabled = isset( $portletData[ 'data-user-menu-logout' ][ 'is-empty' ] ) &&
				!$portletData[ 'data-user-menu-logout'][ 'is-empty' ];
			if ( $isLogoutLinkEnabled ) {
				$dropdownMenus[] = new VectorComponentMenu( [
					'label' => null
				] + $portletData[ 'data-user-menu-logout' ] );
			}
		}

		return $dropdownMenus;
	}

	/**
	 * Strips icons from the menu.
	 *
	 * @param array $arrayListItems
	 * @return array
	 */
	private static function stripIcons( array $arrayListItems ) {
		return array_map( static function ( $item ) {
			$item['array-links'] = array_map( static function ( $link ) {
				$link['icon'] = null;
				return $link;
			}, $item['array-links'] );
			return $item;
		}, $arrayListItems );
	}

	/**
	 * Converts links to button icons
	 *
	 * @param array $arrayListItems
	 * @param bool $iconOnlyButton whether label should be visible.
	 * @param array $exceptions list of names of items that should not be converted.
	 * @return array
	 */
	private static function makeLinksButtons( $arrayListItems, $iconOnlyButton = true, $exceptions = [] ) {
		return array_map( static function ( $item ) use ( $iconOnlyButton, $exceptions ) {
			if ( in_array( $item[ 'name'], $exceptions ) ) {
				return $item;
			}
			$item['array-links'] = array_map( static function ( $link ) use ( $iconOnlyButton ) {
				$link['array-attributes'] = array_map( static function ( $attribute ) use ( $iconOnlyButton ) {
					if ( $attribute['key'] === 'class' ) {
						$newClass = $attribute['value'] . ' ' . self::BUTTON_CLASSES;
						if ( $iconOnlyButton ) {
							$newClass .= ' ' . self::ICON_ONLY_BUTTON_CLASS;
						}
						$attribute['value'] = $newClass;
					}
					return $attribute;
				}, $link['array-attributes'] );
				return $link;
			}, $item['array-links'] );
			return $item;
		}, $arrayListItems );
	}

	/**
	 * Makes all menu items collapsible at lower resolutions.
	 *
	 * @param array $arrayListItems
	 * @return array
	 */
	private static function makeItemsCollapsible( $arrayListItems ) {
		return array_map( static function ( $item ) {
			$item['class'] .= ' user-links-collapsible-item';
			return $item;
		}, $arrayListItems );
	}

	/**
	 * What class should the overflow menu have?
	 *
	 * @param array $arrayListItems
	 * @return string
	 */
	private static function getOverflowMenuClass( $arrayListItems ) {
		$overflowMenuClass = 'mw-portlet';
		if ( count( $arrayListItems ) === 0 ) {
			$overflowMenuClass .= ' emptyPortlet';
		}
		return $overflowMenuClass;
	}

	/**
	 * @inheritDoc
	 */
	public function getTemplateData(): array {
		$portletData = $this->portletData;

		$isDefaultAnonUserLinks = count( $portletData['data-user-menu']['array-items'] ) === 2;
		$isAnonEditorLinksEnabled = isset( $portletData['data-user-menu-anon-editor']['is-empty'] )
			&& !$portletData['data-user-menu-anon-editor']['is-empty'];

		$userInterfacePreferences = $this->makeLinksButtons(
			$this->makeItemsCollapsible(
				$portletData[ 'data-user-interface-preferences' ]['array-items'] ?? []
			),
			false
		);
		$userPage = $this->makeItemsCollapsible(
			$this->stripIcons( $portletData[ 'data-user-page' ]['array-items'] ?? [] )
		);
		$notifications = $this->makeLinksButtons(
			$portletData[ 'data-notifications' ]['array-items'] ?? [],
			true,
			[ 'talk-alert' ]
		);

		$overflow = $this->makeItemsCollapsible(
			array_map(
				static function ( $item ) {
					// Since we're creating duplicate icons
					$item['id'] .= '-2';
					// Restore icon removed in hooks.
					if ( $item['name'] === 'watchlist' ) {
						$item['icon'] = 'watchlist';
					}
					return $item;
				},
				// array_filter preserves keys so use array_values to restore array.
				array_values(
					array_filter(
						$portletData['data-user-menu']['array-items'] ?? [],
						static function ( $item ) {
							// Only certain items get promoted to the overflow menu:
							// * watchlist
							// * login
							// * create account
							$name = $item['name'];
							return in_array( $name, [ 'watchlist', 'createaccount', 'login', 'login-private' ] );
						}
					)
				)
			)
		);
		// Convert to buttons for logged in users.
		// For anons these will remain as links.
		// Note: This list is empty for temporary users currently.
		if ( $this->user->isRegistered() ) {
			$overflow = $this->makeLinksButtons( $overflow );
		}

		$preferencesMenu = new VectorComponentMenu( [
			'id' => 'p-vector-user-menu-preferences',
			'class' => self::getOverflowMenuClass( $userInterfacePreferences ),
			'label' => null,
			'html-items' => null,
			'array-list-items' => $userInterfacePreferences,
		] );
		$userPageMenu = new VectorComponentMenu( [
			'id' => 'p-vector-user-menu-userpage',
			'class' => self::getOverflowMenuClass( $userPage ),
			'label' => null,
			'html-items' => null,
			'array-list-items' => $userPage,
		] );
		$notificationsMenu = new VectorComponentMenu( [
			'id' => 'p-vector-user-menu-notifications',
			'class' => self::getOverflowMenuClass( $notifications ),
			'label' => null,
			'html-items' => null,
			'array-list-items' => $notifications,
		] );
		$overflowMenu = new VectorComponentMenu( [
			'id' => 'p-vector-user-menu-overflow',
			'class' => self::getOverflowMenuClass( $overflow ),
			'label' => null,
			'html-items' => null,
			'array-list-items' => $overflow,
		] );

		return [
			'is-wide' => array_filter(
				[ $overflow, $notifications, $userPage, $userInterfacePreferences ]
			) !== [],
			'data-user-links-notifications' => $notificationsMenu->getTemplateData(),
			'data-user-links-overflow' => $overflowMenu->getTemplateData(),
			'data-user-links-preferences' => $preferencesMenu->getTemplateData(),
			'data-user-links-user-page' => $userPageMenu->getTemplateData(),
			'data-user-links-dropdown' => $this->getDropdown( $isDefaultAnonUserLinks, $isAnonEditorLinksEnabled )
				->getTemplateData(),
			'data-user-links-menus' => array_map( static function ( $menu ) {
				return $menu->getTemplateData();
			}, $this->getMenus( $isDefaultAnonUserLinks, $isAnonEditorLinksEnabled ) ),
		];
	}
}
