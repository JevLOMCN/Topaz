<?php
/**
 * Copyright © 2007 Daniel Kinzler
 *
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

use ApiMessage;
use Content;
use Exception;
use HTMLForm;
use IContextSource;
use InvalidArgumentException;
use ManualLogEntry;
use MediaWiki\Extension\Gadgets\Content\GadgetDefinitionContent;
use MediaWiki\Extension\Gadgets\Special\SpecialGadgetUsage;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\DeleteUnknownPreferencesHook;
use MediaWiki\Hook\EditFilterMergedContentHook;
use MediaWiki\Hook\PreferencesGetIconHook;
use MediaWiki\Hook\PreferencesGetLegendHook;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\Hook\GetUserPermissionsErrorsHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\Revision\Hook\ContentHandlerDefaultModelForHook;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\SpecialPage\Hook\WgQueryPagesHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\Hook\UserGetDefaultOptionsHook;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use MessageSpecifier;
use OOUI\HtmlSnippet;
use RequestContext;
use Skin;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\LikeValue;
use Wikimedia\WrappedString;
use WikiPage;
use Xml;

class Hooks implements
	PageDeleteCompleteHook,
	PageSaveCompleteHook,
	UserGetDefaultOptionsHook,
	GetPreferencesHook,
	PreferencesGetIconHook,
	PreferencesGetLegendHook,
	ResourceLoaderRegisterModulesHook,
	BeforePageDisplayHook,
	EditFilterMergedContentHook,
	ContentHandlerDefaultModelForHook,
	WgQueryPagesHook,
	DeleteUnknownPreferencesHook,
	GetUserPermissionsErrorsHook
{
	private GadgetRepo $gadgetRepo;
	private UserOptionsLookup $userOptionsLookup;

	public function __construct(
		GadgetRepo $gadgetRepo,
		UserOptionsLookup $userOptionsLookup
	) {
		$this->gadgetRepo = $gadgetRepo;
		$this->userOptionsLookup = $userOptionsLookup;
	}

	/**
	 * Handle MediaWiki\Page\Hook\PageSaveCompleteHook
	 *
	 * @param WikiPage $wikiPage
	 * @param mixed $userIdentity unused
	 * @param string $summary
	 * @param int $flags
	 * @param mixed $revisionRecord unused
	 * @param mixed $editResult unused
	 */
	public function onPageSaveComplete(
		$wikiPage,
		$userIdentity,
		$summary,
		$flags,
		$revisionRecord,
		$editResult
	): void {
		$title = $wikiPage->getTitle();
		$this->gadgetRepo->handlePageUpdate( $title );
	}

	/**
	 * Handle MediaWiki\Page\Hook\PageDeleteCompleteHook
	 *
	 * @param ProperPageIdentity $page
	 * @param Authority $deleter
	 * @param string $reason
	 * @param int $pageID
	 * @param RevisionRecord $deletedRev Last revision
	 * @param ManualLogEntry $logEntry
	 * @param int $archivedRevisionCount Number of revisions deleted
	 */
	public function onPageDeleteComplete(
		ProperPageIdentity $page,
		Authority $deleter,
		string $reason,
		int $pageID,
		RevisionRecord $deletedRev,
		ManualLogEntry $logEntry,
		int $archivedRevisionCount
	): void {
		$title = TitleValue::newFromPage( $page );
		$this->gadgetRepo->handlePageUpdate( $title );
	}

	/**
	 * UserGetDefaultOptions hook handler
	 * @param array &$defaultOptions Array of default preference keys and values
	 */
	public function onUserGetDefaultOptions( &$defaultOptions ) {
		$gadgets = $this->gadgetRepo->getStructuredList();
		if ( !$gadgets ) {
			return;
		}

		/**
		 * @var $gadget Gadget
		 */
		foreach ( $gadgets as $thisSection ) {
			foreach ( $thisSection as $gadgetId => $gadget ) {
				// Hidden gadgets don't need to be added here, T299071
				if ( !$gadget->isHidden() ) {
					$defaultOptions['gadget-' . $gadgetId] = $gadget->isOnByDefault() ? 1 : 0;
				}
			}
		}
	}

	/**
	 * GetPreferences hook handler.
	 * @param User $user
	 * @param array &$preferences Preference descriptions
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$gadgets = $this->gadgetRepo->getStructuredList();
		if ( !$gadgets ) {
			return;
		}

		$preferences['gadgets-intro'] = [
			'type' => 'info',
			'default' => wfMessage( 'gadgets-prefstext' )->parseAsBlock(),
			'section' => 'gadgets',
			'raw' => true,
		];

		$safeMode = $this->userOptionsLookup->getOption( $user, 'forcesafemode' );
		if ( $safeMode ) {
			$preferences['gadgets-safemode'] = [
				'type' => 'info',
				'default' => Html::warningBox( wfMessage( 'gadgets-prefstext-safemode' )->parse() ),
				'section' => 'gadgets',
				'raw' => true,
			];
		}

		$skin = RequestContext::getMain()->getSkin();
		foreach ( $gadgets as $section => $thisSection ) {
			foreach ( $thisSection as $gadget ) {
				// Only show option to enable gadget if it can be enabled
				$type = 'api';
				if (
					!$safeMode
					&& !$gadget->isHidden()
					&& $gadget->isAllowed( $user )
					&& $gadget->isSkinSupported( $skin )
				) {
					$type = 'check';
				}
				$gname = $gadget->getName();
				$sectionLabelMsg = "gadget-section-$section";

				$preferences["gadget-$gname"] = [
					'type' => $type,
					'label-message' => $gadget->getDescriptionMessageKey(),
					'section' => $section !== '' ? "gadgets/$sectionLabelMsg" : 'gadgets',
					'default' => $gadget->isEnabled( $user ),
					'noglobal' => true,
				];
			}
		}
	}

	/**
	 * PreferencesGetLegend hook handler.
	 *
	 * Used to override the subsection heading labels for the gadget groups. The default message would
	 * be "prefs-$key", but we've previously used different messages, and they have on-wiki overrides
	 * that would have to be moved if the message keys changed.
	 *
	 * @param HTMLForm $form the HTMLForm object. This is a ContextSource as well
	 * @param string $key the section name
	 * @param string &$legend the legend text. Defaults to wfMessage( "prefs-$key" )->text() but may
	 *   be overridden
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onPreferencesGetLegend( $form, $key, &$legend ) {
		if ( str_starts_with( $key, 'gadget-section-' ) ) {
			$legend = new HtmlSnippet( $form->msg( $key )->parse() );
		}
	}

	/**
	 * Add icon for Special:Preferences mobile layout
	 *
	 * @param array &$iconNames Array of icon names for their respective sections.
	 */
	public function onPreferencesGetIcon( &$iconNames ) {
		$iconNames[ 'gadgets' ] = 'puzzle';
	}

	/**
	 * ResourceLoaderRegisterModules hook handler.
	 * @param ResourceLoader $resourceLoader
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ): void {
		foreach ( $this->gadgetRepo->getGadgetIds() as $id ) {
			$resourceLoader->register( Gadget::getModuleName( $id ), [
				'class' => GadgetResourceLoaderModule::class,
				'id' => $id,
			] );
		}
	}

	/**
	 * BeforePageDisplay hook handler.
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$repo = $this->gadgetRepo;
		$ids = $repo->getGadgetIds();
		if ( !$ids ) {
			return;
		}

		$enabledLegacyGadgets = [];
		$conditions = new GadgetLoadConditions( $out );

		/**
		 * @var $gadget Gadget
		 */
		foreach ( $ids as $id ) {
			try {
				$gadget = $repo->getGadget( $id );
			} catch ( InvalidArgumentException $e ) {
				continue;
			}

			if ( $conditions->check( $gadget ) ) {
				if ( $gadget->hasModule() ) {
					if ( $gadget->getType() === 'styles' ) {
						$out->addModuleStyles( Gadget::getModuleName( $gadget->getName() ) );
					} else {
						$out->addModules( Gadget::getModuleName( $gadget->getName() ) );

						$peers = [];
						foreach ( $gadget->getPeers() as $peerName ) {
							try {
								$peers[] = $repo->getGadget( $peerName );
							} catch ( InvalidArgumentException $e ) {
								// Ignore, warning is emitted on Special:Gadgets
							}
						}
						// Load peer modules
						foreach ( $peers as $peer ) {
							if ( $peer->getType() === 'styles' ) {
								$out->addModuleStyles( Gadget::getModuleName( $peer->getName() ) );
							}
							// Else, if not type=styles: Use dependencies instead.
							// Note: No need for recursion as styles modules don't support
							// either of 'dependencies' and 'peers'.
						}
					}
				}

				if ( $gadget->getLegacyScripts() ) {
					$enabledLegacyGadgets[] = $id;
				}
			}
		}

		$strings = [];
		foreach ( $enabledLegacyGadgets as $id ) {
			$strings[] = $this->makeLegacyWarning( $id );
		}
		$out->addHTML( WrappedString::join( "\n", $strings ) );
	}

	/**
	 * @param string $id
	 * @return string|WrappedString HTML
	 */
	private function makeLegacyWarning( $id ) {
		$special = SpecialPage::getTitleFor( 'Gadgets' );

		return ResourceLoader::makeInlineScript(
			Xml::encodeJsCall( 'mw.log.warn', [
				"Gadget \"$id\" was not loaded. Please migrate it to use ResourceLoader. " .
				'See <' . $special->getCanonicalURL() . '>.'
			] )
		);
	}

	/**
	 * Valid gadget definition page after content is modified
	 *
	 * @param IContextSource $context
	 * @param Content $content
	 * @param Status $status
	 * @param string $summary
	 * @param User $user
	 * @param bool $minoredit
	 * @throws Exception
	 * @return bool
	 */
	public function onEditFilterMergedContent(
		IContextSource $context,
		Content $content,
		Status $status,
		$summary,
		User $user,
		$minoredit
	) {
		if ( $content instanceof GadgetDefinitionContent ) {
			$validateStatus = $content->validate();
			if ( !$validateStatus->isGood() ) {
				$status->merge( $validateStatus );
				return false;
			}
		}

		return true;
	}

	/**
	 * Create "MediaWiki:Gadgets/<id>.json" pages with GadgetDefinitionContent
	 *
	 * @param Title $title
	 * @param string &$model
	 * @return bool
	 */
	public function onContentHandlerDefaultModelFor( $title, &$model ) {
		if ( MediaWikiGadgetsJsonRepo::isGadgetDefinitionTitle( $title ) ) {
			$model = 'GadgetDefinition';
			return false;
		}

		return true;
	}

	/**
	 * Add the GadgetUsage special page to the list of QueryPages.
	 * @param array &$queryPages
	 */
	public function onWgQueryPages( &$queryPages ) {
		$queryPages[] = [ SpecialGadgetUsage::class, 'GadgetUsage' ];
	}

	/**
	 * Prevent gadget preferences from being deleted.
	 * @link https://www.mediawiki.org/wiki/Manual:Hooks/DeleteUnknownPreferences
	 * @param string[] &$where Array of where clause conditions to add to.
	 * @param IDatabase $db
	 */
	public function onDeleteUnknownPreferences( &$where, $db ) {
		$where[] = $db->expr(
			'up_property',
			IExpression::NOT_LIKE,
			new LikeValue( 'gadget-', $db->anyString() )
		);
	}

	/**
	 * @param Title $title Title being checked against
	 * @param User $user Current user
	 * @param string $action Action being checked
	 * @param array|string|MessageSpecifier &$result User permissions error to add. If none, return true.
	 *   For consistency, error messages should be plain text with no special coloring,
	 *   bolding, etc. to show that they're errors; presenting them properly to the
	 *   user as errors is done by the caller.
	 * @return bool|void
	 */
	public function onGetUserPermissionsErrors( $title, $user, $action, &$result ) {
		if ( $action === 'edit'
			&& MediaWikiGadgetsJsonRepo::isGadgetDefinitionTitle( $title )
		) {
			if ( !$user->isAllowed( 'editsitejs' ) ) {
				$result = ApiMessage::create( wfMessage( 'sitejsprotected' ), 'sitejsprotected' );
				return false;
			}
		}
	}
}
