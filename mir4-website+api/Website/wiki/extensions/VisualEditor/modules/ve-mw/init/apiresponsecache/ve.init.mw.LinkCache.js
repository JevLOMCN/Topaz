/*!
 * VisualEditor MediaWiki Initialization LinkCache class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Caches information about titles.
 *
 * @class
 * @extends ve.init.mw.ApiResponseCache
 * @constructor
 * @param {mw.Api} [api]
 */
ve.init.mw.LinkCache = function VeInitMwLinkCache() {
	// Parent constructor
	ve.init.mw.LinkCache.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.init.mw.LinkCache, ve.init.mw.ApiResponseCache );

/* Static methods */

/**
 * Get the icon name to use for a particular link type
 *
 * @param {Object} linkData Link data
 * @return {string} Icon name
 */
ve.init.mw.LinkCache.static.getIconForLink = function ( linkData ) {
	if ( linkData.missing ) {
		return 'articleNotFound';
	}
	if ( linkData.redirect ) {
		return 'articleRedirect';
	}
	if ( linkData.disambiguation ) {
		return 'articleDisambiguation';
	}
	return 'article';
};

/**
 * @inheritdoc
 */
ve.init.mw.LinkCache.static.processPage = function ( page ) {
	return {
		missing: page.missing !== undefined,
		known: page.known !== undefined,
		redirect: page.redirect !== undefined,
		disambiguation: ve.getProp( page, 'pageprops', 'disambiguation' ) !== undefined,
		hidden: ve.getProp( page, 'pageprops', 'hiddencat' ) !== undefined,
		imageUrl: ve.getProp( page, 'thumbnail', 'source' ),
		description: page.description
	};
};

/* Methods */

/**
 * Requests information about the title, then adds classes to the provided element as appropriate.
 *
 * @param {string} title
 * @param {jQuery} $element Element to style
 * @param {boolean} [hasFragment=false] Whether the link goes to a fragment
 */
ve.init.mw.LinkCache.prototype.styleElement = function ( title, $element, hasFragment ) {
	var cache = this,
		cachedMissingData = this.getCached( '_missing/' + title );

	var promise;
	// Use the synchronous missing link cache data if it exists
	if ( cachedMissingData ) {
		promise = ve.createDeferred().resolve( cachedMissingData ).promise();
	} else {
		promise = this.get( title );
	}

	promise.done( function ( data ) {
		if ( data.missing && !data.known ) {
			$element.addClass( 'new' );
		} else {
			// Provided by core MediaWiki, styled like a <strong> element by default.
			if ( !hasFragment && cache.constructor.static.normalizeTitle( title ) === cache.constructor.static.normalizeTitle( mw.config.get( 'wgRelevantPageName' ) ) ) {
				$element.addClass( 'mw-selflink' );
			}
			// Provided by core MediaWiki, no styles by default.
			if ( data.redirect ) {
				$element.addClass( 'mw-redirect' );
			}
			// Provided by the Disambiguator extension, no styles by default.
			if ( data.disambiguation ) {
				$element.addClass( 'mw-disambig' );
			}
		}
	} );
};

/**
 * Given a chunk of Parsoid HTML, applies style transformations.
 *
 * Previously this was used for applying red-link styles, but that
 * has since been upstreamed to Parsoid.
 *
 * TODO: Evaluate if this method should be renamed/removed as it
 * now has nothing to do with the link cache.
 *
 * @param {jQuery} $elements Elements to style
 * @param {HTMLDocument} doc Base document to use for normalisation
 */
ve.init.mw.LinkCache.prototype.styleParsoidElements = function ( $elements ) {
	if ( ve.dm.MWLanguageVariantNode ) {
		// Render the user's preferred variant in language converter markup
		$elements.each( function ( i, element ) {
			ve.dm.MWLanguageVariantNode.static.processVariants( element );
		} );
	}
};

/**
 * Enable or disable automatic assumption of existence.
 *
 * While enabled, any get() for a title that's not already in the cache will return
 * { missing: false } and write that to the cache.
 *
 * @param {boolean} assume Assume all uncached titles exist
 */
ve.init.mw.LinkCache.prototype.setAssumeExistence = function ( assume ) {
	this.assumeExistence = !!assume;
};

/**
 * Set link missing data
 *
 * Stored separately from the full link data cache
 *
 * @param {Object} entries Object keyed by page title, with the values being data objects
 */
ve.init.mw.LinkCache.prototype.setMissing = function ( entries ) {
	var missingEntries = {};
	for ( var name in entries ) {
		missingEntries[ '_missing/' + name ] = entries[ name ];
	}
	this.set( missingEntries );
};

/**
 * @inheritdoc
 */
ve.init.mw.LinkCache.prototype.get = function ( title ) {
	var data = {};
	if ( this.assumeExistence ) {
		data[ this.constructor.static.normalizeTitle( title ) ] = { missing: false };
		this.setMissing( data );
	}

	// Parent method
	return ve.init.mw.LinkCache.super.prototype.get.call( this, title );
};

/**
 * @inheritdoc
 */
ve.init.mw.LinkCache.prototype.getRequestPromise = function ( subqueue ) {
	return this.api.get( {
		action: 'query',
		prop: 'info|pageprops|pageimages|description',
		pithumbsize: 80,
		pilimit: subqueue.length,
		ppprop: 'disambiguation|hiddencat',
		titles: subqueue,
		continue: ''
	} );
};
