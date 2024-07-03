<?php

namespace MediaWiki\Extension\Gadgets;

use InvalidArgumentException;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Message;

abstract class GadgetRepo {

	/**
	 * @var GadgetRepo|null
	 */
	private static $instance;

	/** @internal */
	public const RESOURCE_TITLE_PREFIX = 'MediaWiki:Gadget-';

	/**
	 * Get the ids of the gadgets provided by this repository
	 *
	 * It's possible this could be out of sync with what
	 * getGadget() will return due to caching
	 *
	 * @return string[]
	 */
	abstract public function getGadgetIds(): array;

	/**
	 * Get the Gadget object for a given gadget ID
	 *
	 * @param string $id
	 * @return Gadget
	 * @throws InvalidArgumentException For unregistered ID, used by getStructuredList()
	 */
	abstract public function getGadget( string $id ): Gadget;

	/**
	 * Invalidate any caches based on the provided page (after create, edit, or delete).
	 *
	 * This must be called on create and delete as well (T39228).
	 *
	 * @param LinkTarget $target
	 * @return void
	 */
	public function handlePageUpdate( LinkTarget $target ): void {
	}

	/**
	 * Given a gadget ID, return the title of the page where the gadget is
	 * defined (or null if the given repo does not have per-gadget definition
	 * pages).
	 *
	 * @param string $id
	 * @return Title|null
	 */
	public function getGadgetDefinitionTitle( string $id ): ?Title {
		return null;
	}

	/**
	 * Get a lists of Gadget objects by category
	 *
	 * @return array<string,Gadget[]> `[ 'category' => [ 'name' => $gadget ] ]`
	 */
	public function getStructuredList() {
		$list = [];
		foreach ( $this->getGadgetIds() as $id ) {
			try {
				$gadget = $this->getGadget( $id );
			} catch ( InvalidArgumentException $e ) {
				continue;
			}
			$list[$gadget->getCategory()][$gadget->getName()] = $gadget;
		}

		return $list;
	}

	/**
	 * Get the page name without "MediaWiki:Gadget-" prefix.
	 *
	 * This name is used by `mw.loader.require()` so that `require("./example.json")` resolves
	 * to `MediaWiki:Gadget-example.json`.
	 *
	 * @param string $titleText
	 * @param string $gadgetId
	 * @return string
	 */
	public function titleWithoutPrefix( string $titleText, string $gadgetId ): string {
		$numReplaces = 1; // there will only one occurrence of the prefix
		return str_replace( self::RESOURCE_TITLE_PREFIX, '', $titleText, $numReplaces );
	}

	/**
	 * @param Gadget $gadget
	 * @return Message[]
	 */
	public function validationWarnings( Gadget $gadget ): array {
		// Basic checks local to the gadget definition
		$warningMsgKeys = $gadget->getValidationWarnings();
		$warnings = array_map( static function ( $warningMsgKey ) {
			return wfMessage( $warningMsgKey );
		}, $warningMsgKeys );

		// Check for invalid values in skins, rights, namespaces, and contentModels
		$this->checkInvalidLoadConditions( $gadget, 'skins', $warnings );
		$this->checkInvalidLoadConditions( $gadget, 'rights', $warnings );
		$this->checkInvalidLoadConditions( $gadget, 'namespaces', $warnings );
		$this->checkInvalidLoadConditions( $gadget, 'contentModels', $warnings );

		// Peer gadgets not being styles-only gadgets, or not being defined at all
		foreach ( $gadget->getPeers() as $peer ) {
			try {
				$peerGadget = $this->getGadget( $peer );
				if ( $peerGadget->getType() !== 'styles' ) {
					$warnings[] = wfMessage( "gadgets-validate-invalidpeer", $peer );
				}
			} catch ( InvalidArgumentException $ex ) {
				$warnings[] = wfMessage( "gadgets-validate-nopeer", $peer );
			}
		}

		// Check that the gadget pages exist and are of the right content model
		$warnings = array_merge(
			$warnings,
			$this->checkTitles( $gadget->getScripts(), CONTENT_MODEL_JAVASCRIPT,
				"gadgets-validate-invalidjs" ),
			$this->checkTitles( $gadget->getStyles(), CONTENT_MODEL_CSS,
				"gadgets-validate-invalidcss" ),
			$this->checkTitles( $gadget->getJSONs(), CONTENT_MODEL_JSON,
				"gadgets-validate-invalidjson" )
		);

		return $warnings;
	}

	/**
	 * Check titles used in gadget to verify existence and correct content model.
	 * @param array $pages
	 * @param string $expectedContentModel
	 * @param string $msg
	 * @return Message[]
	 */
	private function checkTitles( array $pages, string $expectedContentModel, string $msg ): array {
		$warnings = [];
		foreach ( $pages as $pageName ) {
			$title = Title::newFromText( $pageName );
			if ( !$title ) {
				$warnings[] = wfMessage( "gadgets-validate-invalidtitle", $pageName );
				continue;
			}
			if ( !$title->exists() ) {
				$warnings[] = wfMessage( "gadgets-validate-nopage", $pageName );
				continue;
			}
			$contentModel = $title->getContentModel();
			if ( $contentModel !== $expectedContentModel ) {
				$warnings[] = wfMessage( $msg, $pageName, $contentModel );
			}
		}
		return $warnings;
	}

	/**
	 * @param Gadget $gadget
	 * @param string $condition
	 * @param Message[] &$warnings
	 */
	private function checkInvalidLoadConditions( Gadget $gadget, string $condition, array &$warnings ) {
		switch ( $condition ) {
			case 'skins':
				$allSkins = array_keys( MediaWikiServices::getInstance()->getSkinFactory()->getInstalledSkins() );
				$this->maybeAddWarnings( $gadget->getRequiredSkins(),
					static function ( $skin ) use ( $allSkins ) {
						return !in_array( $skin, $allSkins, true );
					}, $warnings, "gadgets-validate-invalidskins" );
				break;

			case 'rights':
				$allPerms = MediaWikiServices::getInstance()->getPermissionManager()->getAllPermissions();
				$this->maybeAddWarnings( $gadget->getRequiredRights(),
					static function ( $right ) use ( $allPerms ) {
						return !in_array( $right, $allPerms, true );
					}, $warnings, "gadgets-validate-invalidrights" );
				break;

			case 'namespaces':
				$nsInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
				$this->maybeAddWarnings( $gadget->getRequiredNamespaces(),
					static function ( $ns ) use ( $nsInfo ) {
						return !$nsInfo->exists( $ns );
					}, $warnings, "gadgets-validate-invalidnamespaces"
				);
				break;

			case 'contentModels':
				$contentHandlerFactory = MediaWikiServices::getInstance()->getContentHandlerFactory();
				$this->maybeAddWarnings( $gadget->getRequiredContentModels(),
					static function ( $model ) use ( $contentHandlerFactory ) {
						return !$contentHandlerFactory->isDefinedModel( $model );
					}, $warnings, "gadgets-validate-invalidcontentmodels"
				);
				break;
			default:
		}
	}

	/**
	 * Iterate over the given $entries, for each check if it is invalid using $isInvalid predicate,
	 * and if so add the $message to $warnings.
	 *
	 * @param array $entries
	 * @param callable $isInvalid
	 * @param array &$warnings
	 * @param string $message
	 */
	private function maybeAddWarnings( array $entries, callable $isInvalid, array &$warnings, string $message ) {
		$invalidEntries = [];
		foreach ( $entries as $entry ) {
			if ( $isInvalid( $entry ) ) {
				$invalidEntries[] = $entry;
			}
		}
		if ( count( $invalidEntries ) ) {
			$warnings[] = wfMessage( $message,
				Message::listParam( $invalidEntries, 'comma' ),
				count( $invalidEntries ) );
		}
	}

	/**
	 * Get the configured default GadgetRepo.
	 *
	 * @deprecated Use the GadgetsRepo service instead
	 * @return GadgetRepo
	 */
	public static function singleton() {
		if ( self::$instance === null ) {
			return MediaWikiServices::getInstance()->getService( 'GadgetsRepo' );
		}
		return self::$instance;
	}

	/**
	 * Should only be used by unit tests
	 *
	 * @deprecated Use the GadgetsRepo service instead
	 * @param GadgetRepo|null $repo
	 */
	public static function setSingleton( $repo = null ) {
		self::$instance = $repo;
	}
}
