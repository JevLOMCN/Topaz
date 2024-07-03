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

use MediaWiki\Config\ServiceOptions;
use MediaWiki\MediaWikiServices;
use MediaWiki\Minerva\LanguagesHelper;
use MediaWiki\Minerva\Menu\Definitions;
use MediaWiki\Minerva\Menu\PageActions as PageActionsMenu;
use MediaWiki\Minerva\Permissions\IMinervaPagePermissions;
use MediaWiki\Minerva\Permissions\MinervaPagePermissions;
use MediaWiki\Minerva\SkinOptions;
use MediaWiki\Minerva\Skins\SkinMinerva;
use MediaWiki\Minerva\Skins\SkinUserPageHelper;
use MediaWiki\SpecialPage\SpecialPage;

return [
	'Minerva.Menu.Definitions' => static function ( MediaWikiServices $services ): Definitions {
		return new Definitions(
			$services->getSpecialPageFactory()
		);
	},
	'Minerva.Menu.PageActionsDirector' =>
		static function ( MediaWikiServices $services ): PageActionsMenu\PageActionsDirector {
			/**
			 * @var SkinOptions $skinOptions
			 * @var SkinMinerva $skin
			 * @var SkinUserPageHelper $userPageHelper
			 */
			$skinOptions = $services->getService( 'Minerva.SkinOptions' );
			// FIXME: RequestContext should not be accessed in service container.
			$context = RequestContext::getMain();
			$title = $context->getTitle();
			if ( !$title ) {
				$title = SpecialPage::getTitleFor( 'Badtitle' );
			}
			$user = $context->getUser();
			$userPageHelper = $services->getService( 'Minerva.SkinUserPageHelper' )
				->setContext( $context )
				->setTitle( $title->inNamespace( NS_USER_TALK ) ?
					$context->getSkin()->getRelevantTitle()->getSubjectPage() :
					$title
				);
			$languagesHelper = $services->getService( 'Minerva.LanguagesHelper' );

			$permissions = $services->getService( 'Minerva.Permissions' )
				->setContext( $context );

			$watchlistManager = $services->getWatchlistManager();

			$toolbarBuilder = new PageActionsMenu\ToolbarBuilder(
				$title,
				$user,
				$context,
				$permissions,
				$skinOptions,
				$userPageHelper,
				$languagesHelper,
				new ServiceOptions( PageActionsMenu\ToolbarBuilder::CONSTRUCTOR_OPTIONS,
					$services->getMainConfig() ),
				$watchlistManager
			);
			if ( $skinOptions->get( SkinOptions::TOOLBAR_SUBMENU ) ) {
				$overflowBuilder = $userPageHelper->isUserPage() ?
					new PageActionsMenu\UserNamespaceOverflowBuilder(
						$title,
						$context,
						$permissions,
						$languagesHelper
					) :
					new PageActionsMenu\DefaultOverflowBuilder(
						$title,
						$context,
						$permissions
					);
			} else {
				$overflowBuilder = new PageActionsMenu\EmptyOverflowBuilder();
			}

			return new PageActionsMenu\PageActionsDirector(
				$toolbarBuilder,
				$overflowBuilder,
				$context
			);
		},
	'Minerva.SkinUserPageHelper' => static function ( MediaWikiServices $services ): SkinUserPageHelper {
		return new SkinUserPageHelper(
			$services->getUserFactory(),
			$services->getUserNameUtils()
		);
	},
	'Minerva.LanguagesHelper' => static function ( MediaWikiServices $services ): LanguagesHelper {
		return new LanguagesHelper(
			$services->getLanguageConverterFactory()
		);
	},
	'Minerva.SkinOptions' => static function ( MediaWikiServices $services ): SkinOptions {
		return new SkinOptions(
			$services->getHookContainer(),
			$services->getService( 'Minerva.SkinUserPageHelper' )
		);
	},
	'Minerva.Permissions' => static function ( MediaWikiServices $services ): IMinervaPagePermissions {
		return new MinervaPagePermissions(
			$services->getService( 'Minerva.SkinOptions' ),
			$services->getService( 'Minerva.LanguagesHelper' ),
			$services->getPermissionManager(),
			$services->getContentHandlerFactory(),
			$services->getUserFactory(),
			$services->getWatchlistManager()
		);
	}
];
