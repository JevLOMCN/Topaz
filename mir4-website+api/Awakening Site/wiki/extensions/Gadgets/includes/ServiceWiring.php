<?php

use MediaWiki\Extension\Gadgets\GadgetRepo;
use MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo;
use MediaWiki\Extension\Gadgets\MediaWikiGadgetsJsonRepo;
use MediaWiki\Extension\Gadgets\MultiGadgetRepo;
use MediaWiki\MediaWikiServices;

return [
	'GadgetsRepo' => static function ( MediaWikiServices $services ): GadgetRepo {
		$wanCache = $services->getMainWANObjectCache();
		$revisionLookup = $services->getRevisionLookup();
		$dbProvider = $services->getConnectionProvider();
		switch ( $services->getMainConfig()->get( 'GadgetsRepo' ) ) {
			case 'definition':
				return new MediaWikiGadgetsDefinitionRepo( $dbProvider, $wanCache, $revisionLookup );
			case 'json':
				return new MediaWikiGadgetsJsonRepo( $dbProvider, $wanCache, $revisionLookup );
			case 'json+definition':
				return new MultiGadgetRepo( [
					new MediaWikiGadgetsJsonRepo( $dbProvider, $wanCache, $revisionLookup ),
					new MediaWikiGadgetsDefinitionRepo( $dbProvider, $wanCache, $revisionLookup )
				] );
			default:
				throw new InvalidArgumentException( 'Unexpected value for $wgGadgetsRepo' );
		}
	},
];
