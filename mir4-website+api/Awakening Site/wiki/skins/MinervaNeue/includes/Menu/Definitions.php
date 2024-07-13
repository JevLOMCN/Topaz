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

namespace MediaWiki\Minerva\Menu;

use IContextSource;
use MediaWiki\Minerva\Menu\Entries\AuthMenuEntry;
use MediaWiki\Minerva\Menu\Entries\SingleMenuEntry;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\SpecialPage\SpecialPageFactory;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use Message;

/**
 * Set of all known menu items for easier building
 */
final class Definitions {

	private SpecialPageFactory $specialPageFactory;
	private IContextSource $context;
	private UserIdentity $user;

	/**
	 * Initialize definitions helper class
	 *
	 * @param SpecialPageFactory $specialPageFactory
	 */
	public function __construct(
		SpecialPageFactory $specialPageFactory
	) {
		$this->specialPageFactory = $specialPageFactory;
	}

	/**
	 * @param IContextSource $context
	 * @return $this
	 */
	public function setContext( IContextSource $context ) {
		$this->context = $context;
		$this->user = $context->getUser();
		return $this;
	}

	/**
	 * Builds a meny entry.
	 *
	 * @param string $name
	 * @param string $text Entry label
	 * @param string $url The URL entry points to
	 * @param string $className Optional HTML classes
	 * @param string|null $icon defaults to $name if not specified
	 * @param bool $trackable Whether an entry will track clicks or not. Default is false.
	 * @return SingleMenuEntry
	 */
	private function buildMenuEntry( $name, $text, $url, $className = '', $icon = null, $trackable = false ) {
		$entry = SingleMenuEntry::create( $name, $text, $url, $className, $icon, $trackable );

		return $entry;
	}

	/**
	 * Creates a login or logout button with a profile button.
	 *
	 * @param Group $group
	 */
	public function insertAuthMenuItem( Group $group ) {
		$group->insertEntry( new AuthMenuEntry(
				$this->user,
				$this->context,
				$this->newLogInOutQuery( $this->newReturnToQuery() )
		) );
	}

	/**
	 * Perform message localization
	 * @param string $key to localize
	 * @return Message
	 */
	public function msg( string $key ) {
		return $this->context->msg( $key );
	}

	/**
	 * If Nearby is supported, build and inject the Nearby link
	 * @param Group $group
	 */
	public function insertNearbyIfSupported( Group $group ) {
		// Nearby link (if supported)
		if ( $this->specialPageFactory->exists( 'Nearby' ) ) {
			$entry = $this->buildMenuEntry(
				'nearby',
				$this->context->msg( 'mobile-frontend-main-menu-nearby' )->text(),
				SpecialPage::getTitleFor( 'Nearby' )->getLocalURL(),
				'',
				'mapPin',
				true
			);
			// Setting this feature for javascript only
			$entry->setJSOnly();
			$group->insertEntry( $entry );
		}
	}

	/**
	 * Build and insert the Settings link
	 * @param Group $group
	 */
	public function insertMobileOptionsItem( Group $group ) {
		$title = $this->context->getTitle();
		$config = $this->context->getConfig();
		$returnToTitle = $title->getPrefixedText();
		$user = $this->user;
		$betaEnabled = $config->get( 'MFEnableBeta' );
		/*
		 * to avoid linking to an empty settings page we make this jsonly when:
		 * - AMC and beta is disabled (if logged in there is nothing to show)
		 * - user is logged out and beta is disabled (beta is the only thing a non-js user can do)
		 * In future we might want to make this a static function on Special:MobileOptions.
		 */
		$jsonly = ( !$user->isRegistered() && !$betaEnabled ) ||
			( $user->isRegistered() && !$config->get( 'MFAdvancedMobileContributions' ) &&
				!$betaEnabled
			);

		$entry = $this->buildMenuEntry(
			'settings',
			$this->context->msg( 'mobile-frontend-main-menu-settings' )->text(),
			SpecialPage::getTitleFor( 'MobileOptions' )
				->getLocalURL( [ 'returnto' => $returnToTitle ] ),
			'',
			null,
			true
		);
		if ( $jsonly ) {
			$entry->setJSOnly();
		}
		$group->insertEntry( $entry );
	}

	/**
	 * Build and insert the Preferences link
	 * @param Group $group
	 */
	public function insertPreferencesItem( Group $group ) {
		$entry = $this->buildMenuEntry(
			'preferences',
			$this->context->msg( 'preferences' )->text(),
			SpecialPage::getTitleFor( 'Preferences' )->getLocalURL(),
			'',
			'settings',
			true
		);
		$group->insertEntry( $entry );
	}

	/**
	 * Build and insert About page link
	 * @param Group $group
	 */
	public function insertAboutItem( Group $group ) {
		$msg = $this->context->msg( 'aboutsite' );
		if ( $msg->isDisabled() ) {
			return;
		}
		$title = Title::newFromText( $this->context->msg( 'aboutpage' )->inContentLanguage()->text() );
		if ( !$title ) {
			return;
		}
		$entry = $this->buildMenuEntry( 'about', $msg->text(), $title->getLocalURL() );
		$entry->setIcon( null );
		$group->insertEntry( $entry );
	}

	/**
	 * Build and insert Disclaimers link
	 * @param Group $group
	 */
	public function insertDisclaimersItem( Group $group ) {
		$msg = $this->context->msg( 'disclaimers' );
		if ( $msg->isDisabled() ) {
			return;
		}
		$title = Title::newFromText( $this->context->msg( 'disclaimerpage' )
			->inContentLanguage()->text() );
		if ( !$title ) {
			return;
		}
		$entry = $this->buildMenuEntry( 'disclaimers', $msg->text(), $title->getLocalURL() );
		$entry->setIcon( null );
		$group->insertEntry( $entry );
	}

	/**
	 * Build and insert the RecentChanges link
	 * @param Group $group
	 */
	public function insertRecentChanges( Group $group ) {
		$entry = $this->buildMenuEntry(
			'recentchanges',
			$this->context->msg( 'recentchanges' )->escaped(),
			SpecialPage::getTitleFor( 'Recentchanges' )->getLocalURL(),
			'',
			'recentChanges',
			true
		);
		$group->insertEntry( $entry );
	}

	/**
	 * Build and insert the SpecialPages link
	 * @param Group $group
	 */
	public function insertSpecialPages( Group $group ) {
		$entry = $this->buildMenuEntry(
			'specialPages',
			$this->context->msg( 'specialpages' )->text(),
			SpecialPage::getTitleFor( 'Specialpages' )->getLocalURL(),
			'',
			null,
			true
		);
		$group->insertEntry( $entry );
	}

	/**
	 * Build and insert the CommunityPortal link
	 * @param Group $group
	 */
	public function insertCommunityPortal( Group $group ) {
		$msg = $this->context->msg( 'portal' );
		if ( $msg->isDisabled() ) {
			return;
		}
		$title = Title::newFromText( $this->context->msg( 'portal-url' )
			->inContentLanguage()->text() );
		if ( !$title ) {
			return;
		}
		$entry = $this->buildMenuEntry(
			'speechBubbles',
			$msg->text(),
			$title->getLocalURL(),
			'',
			null,
			true
		);
		$group->insertEntry( $entry );
	}

	/**
	 * @param array $returnToQuery
	 * @return array
	 */
	private function newLogInOutQuery( array $returnToQuery ): array {
		$ret = [];
		$title = $this->context->getTitle();
		if ( $title && !$title->isSpecial( 'Userlogin' ) ) {
			$ret[ 'returnto' ] = $title->getPrefixedText();
		}
		if ( $this->user && !$this->user->isRegistered() ) {
			// unset campaign on login link so as not to interfere with A/B tests
			unset( $returnToQuery['campaign'] );
		}
		if ( $returnToQuery ) {
			$ret['returntoquery'] = wfArrayToCgi( $returnToQuery );
		}
		return $ret;
	}

	/**
	 * Retrieve current query parameters from Request object so system can pass those
	 * to the Login/logout links
	 * Some parameters are disabled (like title), as the returnto will be replaced with
	 * the current page.
	 * @return array
	 */
	private function newReturnToQuery(): array {
		$returnToQuery = [];
		$request = $this->context->getRequest();
		if ( !$request->wasPosted() ) {
			$returnToQuery = $request->getValues();
			unset( $returnToQuery['title'] );
			unset( $returnToQuery['returnto'] );
			unset( $returnToQuery['returntoquery'] );
		}
		return $returnToQuery;
	}

	/**
	 * Insert the Donate Link in the Mobile Menu.
	 *
	 * @param Group $group
	 */
	public function insertDonateItem( Group $group ) {
		$labelMsg = $this->context->msg( 'sitesupport' );
		$urlMsg = $this->context->msg( 'sitesupport-url' );
		if ( !$urlMsg->exists() || $labelMsg->isDisabled() ) {
			return;
		}
		// Add term field to allow distinguishing from other sidebars.
		// https://www.mediawiki.org/wiki/Wikimedia_Product/Analytics_Infrastructure/Schema_fragments#Campaign_Attribution
		$url = wfAppendQuery(
			$urlMsg->text(),
			[ 'utm_key' => 'minerva' ]
		);
		$entry = $this->buildMenuEntry(
			'donate',
			$labelMsg->text(),
			$url,
			'',
			'heart',
			true
		);
		$group->insertEntry( $entry );
	}
}
