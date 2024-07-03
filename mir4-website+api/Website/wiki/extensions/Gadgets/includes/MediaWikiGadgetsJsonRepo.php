<?php

namespace MediaWiki\Extension\Gadgets;

use InvalidArgumentException;
use MediaWiki\Extension\Gadgets\Content\GadgetDefinitionContent;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use WANObjectCache;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\LikeValue;

/**
 * Gadgets repo powered by `MediaWiki:Gadgets/<id>.json` pages.
 *
 * Each gadget has its own gadget definition page, using GadgetDefinitionContent.
 */
class MediaWikiGadgetsJsonRepo extends GadgetRepo {
	/**
	 * How long in seconds the list of gadget ids and
	 * individual gadgets should be cached for (1 day)
	 */
	private const CACHE_TTL = 86400;

	public const DEF_PREFIX = 'Gadgets/';
	public const DEF_SUFFIX = '.json';

	private IConnectionProvider $dbProvider;
	private WANObjectCache $wanCache;
	private RevisionLookup $revLookup;

	public function __construct(
		IConnectionProvider $dbProvider,
		WANObjectCache $wanCache,
		RevisionLookup $revLookup
	) {
		$this->dbProvider = $dbProvider;
		$this->wanCache = $wanCache;
		$this->revLookup = $revLookup;
	}

	/**
	 * Get a list of gadget ids from cache/database
	 *
	 * @return string[]
	 */
	public function getGadgetIds(): array {
		$key = $this->getGadgetIdsKey();

		$fname = __METHOD__;
		$dbr = $this->dbProvider->getReplicaDatabase();
		$titles = $this->wanCache->getWithSetCallback(
			$key,
			self::CACHE_TTL,
			static function ( $oldValue, &$ttl, array &$setOpts ) use ( $fname, $dbr ) {
				$setOpts += Database::getCacheSetOptions( $dbr );

				return $dbr->newSelectQueryBuilder()
					->select( 'page_title' )
					->from( 'page' )
					->where( [
						'page_namespace' => NS_MEDIAWIKI,
						'page_content_model' => 'GadgetDefinition',
						$dbr->expr(
							'page_title',
							IExpression::LIKE,
							new LikeValue( self::DEF_PREFIX, $dbr->anyString(), self::DEF_SUFFIX )
						)
					] )
					->caller( $fname )
					->fetchFieldValues();
			},
			[
				'checkKeys' => [ $key ],
				'pcTTL' => WANObjectCache::TTL_PROC_SHORT,
				// Bump when changing the database query.
				'version' => 2,
				'lockTSE' => 30
			]
		);

		$ids = [];
		foreach ( $titles as $title ) {
			$id = self::getGadgetId( $title );
			if ( $id !== '' ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * @inheritDoc
	 */
	public function handlePageUpdate( LinkTarget $target ): void {
		if ( $this->isGadgetDefinitionTitle( $target ) ) {
			$this->purgeGadgetIdsList();
			$this->purgeGadgetEntry( self::getGadgetId( $target->getText() ) );
		}
	}

	/**
	 * Purge the list of gadget ids when a page is deleted or if a new page is created
	 */
	public function purgeGadgetIdsList(): void {
		$this->wanCache->touchCheckKey( $this->getGadgetIdsKey() );
	}

	/**
	 * @param string $title Gadget definition page title
	 * @return string Gadget ID
	 */
	private static function getGadgetId( string $title ): string {
		if ( !str_starts_with( $title, self::DEF_PREFIX ) || !str_ends_with( $title, self::DEF_SUFFIX ) ) {
			throw new InvalidArgumentException( 'Invalid definition page title' );
		}
		return substr( $title, strlen( self::DEF_PREFIX ), -strlen( self::DEF_SUFFIX ) );
	}

	/**
	 * @param LinkTarget $target
	 * @return bool
	 */
	public static function isGadgetDefinitionTitle( LinkTarget $target ): bool {
		if ( !$target->inNamespace( NS_MEDIAWIKI ) ) {
			return false;
		}
		$title = $target->getText();
		try {
			self::getGadgetId( $title );
			return true;
		} catch ( InvalidArgumentException $e ) {
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getGadgetDefinitionTitle( string $id ): ?Title {
		return Title::makeTitleSafe( NS_MEDIAWIKI, self::DEF_PREFIX . $id . self::DEF_SUFFIX );
	}

	/**
	 * @param string $id
	 * @throws InvalidArgumentException
	 * @return Gadget
	 */
	public function getGadget( string $id ): Gadget {
		$key = $this->getGadgetCacheKey( $id );
		$gadget = $this->wanCache->getWithSetCallback(
			$key,
			self::CACHE_TTL,
			function ( $old, &$ttl, array &$setOpts ) use ( $id ) {
				$setOpts += Database::getCacheSetOptions( $this->dbProvider->getReplicaDatabase() );
				$title = $this->getGadgetDefinitionTitle( $id );
				if ( !$title ) {
					$ttl = WANObjectCache::TTL_UNCACHEABLE;
					return null;
				}

				$revRecord = $this->revLookup->getRevisionByTitle( $title );
				if ( !$revRecord ) {
					$ttl = WANObjectCache::TTL_UNCACHEABLE;
					return null;
				}

				$content = $revRecord->getContent( SlotRecord::MAIN );
				if ( !$content instanceof GadgetDefinitionContent ) {
					// Uhm...
					$ttl = WANObjectCache::TTL_UNCACHEABLE;
					return null;
				}

				$handler = $content->getContentHandler();
				'@phan-var \MediaWiki\Extension\Gadgets\Content\GadgetDefinitionContentHandler $handler';
				$data = wfArrayPlus2d( $content->getAssocArray(), $handler->getDefaultMetadata() );
				return Gadget::serializeDefinition( $id, $data );
			},
			[
				'checkKeys' => [ $key ],
				'pcTTL' => WANObjectCache::TTL_PROC_SHORT,
				'lockTSE' => 30,
				'version' => 2,
			]
		);

		if ( $gadget === null ) {
			throw new InvalidArgumentException( "Unknown gadget $id" );
		}

		return new Gadget( $gadget );
	}

	/**
	 * Update the cache for a specific Gadget whenever it is updated
	 *
	 * @param string $id
	 */
	public function purgeGadgetEntry( $id ) {
		$this->wanCache->touchCheckKey( $this->getGadgetCacheKey( $id ) );
	}

	/**
	 * @return string
	 */
	private function getGadgetIdsKey() {
		return $this->wanCache->makeKey( 'gadgets-jsonrepo-ids' );
	}

	/**
	 * @param string $id
	 * @return string
	 */
	private function getGadgetCacheKey( $id ) {
		return $this->wanCache->makeKey( 'gadgets-object', $id, Gadget::GADGET_CLASS_VERSION );
	}
}
