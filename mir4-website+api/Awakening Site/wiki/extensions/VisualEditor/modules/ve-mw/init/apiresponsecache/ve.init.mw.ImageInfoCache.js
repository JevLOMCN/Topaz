/*!
 * VisualEditor MediaWiki Initialization ImageInfoCache class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Get information about images.
 *
 * @class
 * @extends ve.init.mw.ApiResponseCache
 * @constructor
 * @param {mw.Api} [api]
 */
ve.init.mw.ImageInfoCache = function VeInitMwImageInfoCache() {
	// Parent constructor
	ve.init.mw.ImageInfoCache.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.init.mw.ImageInfoCache, ve.init.mw.ApiResponseCache );

/* Static methods */

/**
 * @inheritdoc
 */
ve.init.mw.ImageInfoCache.static.processPage = function ( page ) {
	if ( page.imageinfo ) {
		return page.imageinfo[ 0 ];
	} else if ( 'missing' in page ) {
		return { missing: true };
	}
};

/* Methods */

/**
 * @inheritdoc
 */
ve.init.mw.ImageInfoCache.prototype.getRequestPromise = function ( subqueue ) {
	// If you change what `iiprop`s are being fetched, update
	// ve.ui.MWMediaDialog to add the same ones to the cache.
	return this.api.get(
		{
			action: 'query',
			prop: 'imageinfo',
			indexpageids: '1',
			iiprop: 'size|mediatype',
			titles: subqueue
		},
		{ type: 'POST' }
	);
};
