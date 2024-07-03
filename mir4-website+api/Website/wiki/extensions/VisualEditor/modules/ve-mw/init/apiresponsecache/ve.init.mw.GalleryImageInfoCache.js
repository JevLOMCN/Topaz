/*!
 * VisualEditor MediaWiki Initialization GalleryImageInfoCache class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Get thumbnail URL information about gallery images.
 *
 * @class
 * @extends ve.init.mw.ImageInfoCache
 * @constructor
 * @param {mw.Api} [api]
 */
ve.init.mw.GalleryImageInfoCache = function VeInitMwGalleryImageInfoCache() {
	// Parent constructor
	ve.init.mw.GalleryImageInfoCache.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.init.mw.GalleryImageInfoCache, ve.init.mw.ImageInfoCache );

/* Methods */

/**
 * @inheritdoc
 */
ve.init.mw.GalleryImageInfoCache.prototype.getRequestPromise = function ( subqueue ) {
	return this.api.get(
		{
			action: 'query',
			prop: 'imageinfo',
			iiprop: 'url|size',
			titles: subqueue,
			iiurlwidth: 200,
			iiurlheight: 200
		},
		{ type: 'POST' }
	);
};
