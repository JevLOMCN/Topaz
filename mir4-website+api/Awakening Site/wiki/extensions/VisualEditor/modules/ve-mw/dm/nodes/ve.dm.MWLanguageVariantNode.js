/*!
 * VisualEditor DataModel MWLanguageVariantNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki language variant node, used to represent
 * LanguageConverter markup.
 *
 * @class
 * @abstract
 * @extends ve.dm.LeafNode
 * @mixins ve.dm.FocusableNode
 *
 * @constructor
 */
ve.dm.MWLanguageVariantNode = function VeDmMWLanguageVariantNode() {
	// Parent constructor
	ve.dm.MWLanguageVariantNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.FocusableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWLanguageVariantNode, ve.dm.LeafNode );

OO.mixinClass( ve.dm.MWLanguageVariantNode, ve.dm.FocusableNode );

/* Static members */

ve.dm.MWLanguageVariantNode.static.name = 'mwLanguageVariant';

ve.dm.MWLanguageVariantNode.static.matchTagNames = null;

ve.dm.MWLanguageVariantNode.static.matchRdfaTypes = [ 'mw:LanguageVariant' ];

ve.dm.MWLanguageVariantNode.static.getHashObject = function ( dataElement ) {
	return {
		type: dataElement.type,
		variantInfo: dataElement.attributes.variantInfo
	};
};

/**
 * Node type to use when the contents are inline
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.dm.MWLanguageVariantNode.static.inlineType = 'mwLanguageVariantInline';

/**
 * Node type to use when the contents are a block
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.dm.MWLanguageVariantNode.static.blockType = 'mwLanguageVariantBlock';

/**
 * Node type to use when the contents are hidden
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.dm.MWLanguageVariantNode.static.hiddenType = 'mwLanguageVariantHidden';

/**
 * Migrate field names from old Parsoid spec to new field names.
 * This method will go away after the next Parsoid flag day.
 *
 * @static
 * @private
 * @param {Object} dataMwv
 * @return {Object}
 */
ve.dm.MWLanguageVariantNode.static.migrateFieldNames = function ( dataMwv ) {
	// Field name migration: `bidir`=>`twoway`; `unidir`=>`oneway`
	// This will go away eventually.
	if ( dataMwv.bidir ) {
		dataMwv.twoway = dataMwv.bidir;
		delete dataMwv.bidir;
	}
	if ( dataMwv.unidir ) {
		dataMwv.oneway = dataMwv.unidir;
		delete dataMwv.unidir;
	}
	return dataMwv;
};

/**
 * @inheritdoc
 */
ve.dm.MWLanguageVariantNode.static.toDataElement = function ( domElements, converter ) {
	var firstElement = domElements[ 0 ],
		dataMwvJSON = firstElement.getAttribute( 'data-mw-variant' ),
		dataMwv = dataMwvJSON ? JSON.parse( dataMwvJSON ) : {};

	this.migrateFieldNames( dataMwv );

	var dataElement = {
		attributes: {
			variantInfo: dataMwv,
			originalVariantInfo: dataMwvJSON
		}
	};

	if ( firstElement.tagName === 'META' ) {
		dataElement.type = this.hiddenType;
		return dataElement;
	}

	var isInline = this.isHybridInline( domElements, converter );
	dataElement.type = isInline ? this.inlineType : this.blockType;
	return dataElement;
};

/**
 * @inheritdoc
 */
ve.dm.MWLanguageVariantNode.static.toDomElements = function ( dataElement, doc, converter ) {
	var variantInfo = dataElement.attributes.variantInfo,
		tagName = this.matchTagNames[ 0 ],
		rdfaType = this.matchRdfaTypes[ 0 ],
		domElement = doc.createElement( tagName ),
		dataMwvJSON = JSON.stringify( variantInfo );

	// Preserve exact equality of this attribute for selser.
	if ( dataElement.attributes.originalVariantInfo ) {
		if ( OO.compare(
			this.migrateFieldNames(
				JSON.parse( dataElement.attributes.originalVariantInfo )
			),
			variantInfo
		) ) {
			dataMwvJSON = dataElement.attributes.originalVariantInfo;
		}
	}

	domElement.setAttribute( 'typeof', rdfaType );
	domElement.setAttribute( 'data-mw-variant', dataMwvJSON );
	if ( converter.doesModeNeedRendering() && tagName !== 'META' ) {
		// Fill in contents of span for diff/cut-and-paste/etc.
		this.insertPreviewElements( domElement, variantInfo );
	}
	return [ domElement ];
};

/**
 * Add previews for language variant markup inside their &lt;span> nodes.
 * This ensures that template expansion, cut-and-paste, etc have reasonable
 * renderings.
 *
 * @static
 * @param {HTMLElement} container Container element to process
 * @param {Object|null} opts Preview options
 * @param {boolean} [opts.describeAll=false] Treat all rules as if the
 *   "describe" flag was set. This displays every language and its associated
 *   text, not just the one appropriate for the current user.
 */
ve.dm.MWLanguageVariantNode.static.processVariants = function ( container, opts ) {
	var self = this;

	Array.prototype.forEach.call( container.querySelectorAll( '[typeof="mw:LanguageVariant"]' ), function ( element ) {
		var dataMwvJSON = element.getAttribute( 'data-mw-variant' );
		if ( dataMwvJSON && element.tagName !== 'META' ) {
			self.insertPreviewElements(
				element, JSON.parse( dataMwvJSON ), opts
			);
		}
	} );
};

/**
 * Insert language variant preview for specified element.
 *
 * @static
 * @param {HTMLElement} element Element to insert preview inside of.
 * @param {Object} variantInfo Language variant information object.
 * @param {Object|null} opts Preview options
 * @param {boolean} [opts.describeAll=false] Treat all rules as if the
 *   "describe" flag was set. This displays every language and its associated
 *   text, not just the one appropriate for the current user.
 * @return {HTMLElement} el
 */
ve.dm.MWLanguageVariantNode.static.insertPreviewElements = function ( element, variantInfo, opts ) {
	// Note that `element` can't be a <meta> (or other void tag)
	element.innerHTML = this.getPreviewHtml( variantInfo, opts );
	// This recurses into the children added by the `html` clause to ensure
	// that nested variants are expanded.
	this.processVariants( element, opts );
	return element;
};

/**
 * Helper method to return an appropriate HTML preview string for a
 * language converter node, based on the language variant information
 * object and the user's currently preferred variant.
 *
 * @static
 * @private
 * @param {Object} variantInfo Language variant information object.
 * @param {Object|null} opts Preview options
 * @param {boolean} [opts.describeAll=false] Treat all rules as if the
 *   "describe" flag was set. This displays every language and its associated
 *   text, not just the one appropriate for the current user.
 * @return {string} HTML string
 */
ve.dm.MWLanguageVariantNode.static.getPreviewHtml = function ( variantInfo, opts ) {
	if ( variantInfo.disabled ) {
		return variantInfo.disabled.t;
	} else if ( variantInfo.name ) {
		return ve.init.platform.getLanguageName( variantInfo.name.t.toLowerCase() );
	} else if ( variantInfo.filter ) {
		return variantInfo.filter.t;
	} else if ( variantInfo.describe || ( opts && opts.describeAll ) ) {
		var html = '';
		if ( variantInfo.twoway && variantInfo.twoway.length ) {
			variantInfo.twoway.forEach( function ( item ) {
				html += ve.init.platform.getLanguageName( item.l.toLowerCase() ) + ':' +
					item.t + ';';
			} );
		} else if ( variantInfo.oneway && variantInfo.oneway.length ) {
			variantInfo.oneway.forEach( function ( item ) {
				html += item.f + '⇒' +
					ve.init.platform.getLanguageName( item.l.toLowerCase() ) + ':' +
					item.t + ';';
			} );
		}
		return html;
	} else {
		var languageIndex;
		if ( variantInfo.twoway && variantInfo.twoway.length ) {
			languageIndex = this.matchLanguage( variantInfo.twoway );
			return variantInfo.twoway[ languageIndex ].t;
		} else if ( variantInfo.oneway && variantInfo.oneway.length ) {
			languageIndex = this.matchLanguage( variantInfo.oneway );
			return variantInfo.oneway[ languageIndex ].t;
		}
	}
	return '';
};

/**
 * @inheritdoc
 */
ve.dm.MWLanguageVariantNode.static.describeChanges = function () {
	// TODO: Provide a more detailed description of markup changes
	return ve.msg( 'visualeditor-changedesc-mwlanguagevariant' );
};

/**
 * @inheritdoc ve.dm.Node
 */
ve.dm.MWLanguageVariantNode.static.cloneElement = function () {
	// Parent method
	var clone = ve.dm.MWLanguageVariantNode.super.static.cloneElement.apply( this, arguments );
	delete clone.attributes.originalVariantInfo;
	return clone;
};

/**
 * Match the currently-selected language variant against the most appropriate
 * among a provided list of language codes.
 *
 * @static
 * @param {Object[]} [items] An array of objects, each of which have a field
 *  named `l` equal to a language code.
 * @return {number} The index in `items` with the most appropriate language
 *  code.
 */
ve.dm.MWLanguageVariantNode.static.matchLanguage = function ( items ) {
	var userVariant = mw.config.get( 'wgUserVariant' ),
		fallbacks = mw.config.get( 'wgVisualEditor' ).pageVariantFallbacks,
		languageCodes =
			( userVariant ? [ userVariant ] : [] ).concat( fallbacks || [] );
	for ( var j = 0; j < languageCodes.length; j++ ) {
		var code = languageCodes[ j ].toLowerCase();
		for ( var i = 0; i < items.length; i++ ) {
			if (
				items[ i ].l === '*' ||
				items[ i ].l.toLowerCase() === code
			) {
				return i;
			}
		}
	}
	// Bail: just show the first item.
	return 0;
};

/* Methods */

/**
 * Helper function to get the description object for this markup node.
 *
 * @return {Object}
 */
ve.dm.MWLanguageVariantNode.prototype.getVariantInfo = function () {
	return this.element.attributes.variantInfo;
};

/**
 * Helper function to discriminate between hidden and shown rules.
 *
 * @return {boolean} True if this node represents a conversion rule
 *  with no shown output
 */
ve.dm.MWLanguageVariantNode.prototype.isHidden = function () {
	return false;
};

/**
 * Helper function to discriminate between various types of language
 * converter markup.
 *
 * @return {string}
 */
ve.dm.MWLanguageVariantNode.prototype.getRuleType = function () {
	return this.constructor.static.getRuleType( this.getVariantInfo() );
};

/**
 * Helper function to discriminate between various types of language
 * converter markup.
 *
 * @static
 * @param {Object} variantInfo Language variant information object.
 * @return {string}
 */
ve.dm.MWLanguageVariantNode.static.getRuleType = function ( variantInfo ) {
	if ( variantInfo.disabled ) {
		return 'disabled';
	}
	if ( variantInfo.filter ) {
		return 'filter';
	}
	if ( variantInfo.name ) {
		return 'name';
	}
	if ( variantInfo.twoway ) {
		return 'twoway';
	}
	if ( variantInfo.oneway ) {
		return 'oneway';
	}
	return 'unknown'; // should never happen
};
