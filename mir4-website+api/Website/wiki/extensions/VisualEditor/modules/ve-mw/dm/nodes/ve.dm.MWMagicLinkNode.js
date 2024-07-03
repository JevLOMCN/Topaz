/*!
 * VisualEditor DataModel MWMagicLinkNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki magic link node.
 *
 * @class
 * @extends ve.dm.LeafNode
 * @mixins ve.dm.FocusableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWMagicLinkNode = function VeDmMWMagicLinkNode() {
	// Parent constructor
	ve.dm.MWMagicLinkNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.FocusableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWMagicLinkNode, ve.dm.LeafNode );

OO.mixinClass( ve.dm.MWMagicLinkNode, ve.dm.FocusableNode );

/* Static Properties */

ve.dm.MWMagicLinkNode.static.name = 'link/mwMagic';

ve.dm.MWMagicLinkNode.static.isContent = true;

ve.dm.MWMagicLinkNode.static.matchTagNames = [ 'a' ];

ve.dm.MWMagicLinkNode.static.matchRdfaTypes = [ 'mw:WikiLink', 'mw:ExtLink' ];

// Allow additional 'rel' values in Parsoid output (T321437)
ve.dm.MWMagicLinkNode.static.allowedRdfaTypes = [ 'nofollow', 'noreferrer', 'noopener' ];

ve.dm.MWMagicLinkNode.static.disallowedAnnotationTypes = [ 'link' ];

/**
 * Determine whether the given `element` is a magic link.
 *
 * @param {HTMLElement} element
 * @return {boolean} True if the element is a magic link
 */
ve.dm.MWMagicLinkNode.static.matchFunction = function ( element ) {
	var children = element.childNodes,
		href = element.getAttribute( 'href' );
	// All children must be text nodes, or a <span> representing an entity.
	for ( var i = 0; i < children.length; i++ ) {
		if ( children[ i ].nodeType === Node.TEXT_NODE ) {
			continue;
		}
		// <span typeof='mw:Entity'>...</span> (for &nbsp;)
		if ( children[ i ].nodeType === Node.ELEMENT_NODE &&
			children[ i ].tagName === 'SPAN' &&
			children[ i ].getAttribute( 'typeof' ) === 'mw:Entity' ) {
			continue;
		}
		return false;
	}

	// Check that text content matches one of the magic link types and that
	// the href matches that expected for the magic link type.
	return ve.dm.MWMagicLinkNode.static.validateHref( element.textContent, href );
};

/**
 * Test that a proposed content string is valid for a magic link.
 * If `optType` is given, additionally verify that the content string is
 * valid for the particular type of magic link (RFC/ISBN/PMID).
 *
 * @param {string} content
 *   The content string to test.
 * @param {string} [optType]
 *   The desired type of magic link, one of "RFC", "ISBN", or "PMID".
 *   If not supplied, returns true if the content is valid for any one
 *   of these.
 * @return {boolean}
 *   True if the content string is valid for a magic link of the appropriate
 *   type (or any type).
 */
ve.dm.MWMagicLinkNode.static.validateContent = function ( content, optType ) {
	var type = ve.dm.MWMagicLinkType.static.fromContent( content );
	if ( type === null || ( optType !== undefined && type.type !== optType ) ) {
		// Not a valid magic link, or a magic link of the wrong type.
		return false;
	}
	return true;
};

/**
 * Test that a proposed content string and href is valid for a magic link.
 *
 * @param {string} content
 *   The content string to test.
 * @param {string} href
 *   The URL target of the magic link.
 * @return {boolean}
 *   True if the content string and href are valid for a magic link.
 */
ve.dm.MWMagicLinkNode.static.validateHref = function ( content, href ) {
	var type = ve.dm.MWMagicLinkType.static.fromContent( content );
	return type && type.matchHref( href );
};

/**
 * Return a link annotation appropriate for converting a magic link
 * with the given content into a simple link, or `null` if the given
 * content is not a valid magic link.
 *
 * @param {string} content
 * @return {ve.dm.MWExternalLinkAnnotation|ve.dm.MWInternalLinkAnnotation|null}
 */
ve.dm.MWMagicLinkNode.static.annotationFromContent = function ( content ) {
	var type = ve.dm.MWMagicLinkType.static.fromContent( content );
	return type !== null ? type.getAnnotation() : null;
};

/**
 * @inheritdoc
 */
ve.dm.MWMagicLinkNode.static.toDataElement = function ( domElements ) {
	var textContent = domElements[ 0 ].textContent,
		htmlContent = domElements[ 0 ].innerHTML;
	return {
		type: this.name,
		attributes: {
			content: textContent,
			// These next two attributes allow lossless round-tripping
			// if the original wikitext contained html entities like
			// &nbsp;
			origText: textContent,
			origHtml: htmlContent
		}
	};
};

/**
 * @inheritdoc
 */
ve.dm.MWMagicLinkNode.static.toDomElements = function ( dataElement, doc ) {
	var content = dataElement.attributes.content,
		type = ve.dm.MWMagicLinkType.static.fromContent( content ),
		href = type.getHref(),
		domElement = doc.createElement( 'a' );
	domElement.setAttribute( 'href', href );
	domElement.setAttribute( 'rel', type.rel );
	if ( content === dataElement.attributes.origText ) {
		// Preserve <span typeof="mw:Entity"> elements from the original.
		domElement.innerHTML = dataElement.attributes.origHtml;
	} else {
		domElement.textContent = content;
	}
	return [ domElement ];
};

/* Methods */

/**
 * Return the link target appropriate for this magic link node.
 *
 * @return {string} Link href
 */
ve.dm.MWMagicLinkNode.prototype.getHref = function () {
	var content = this.element.attributes.content,
		type = ve.dm.MWMagicLinkType.static.fromContent( content );
	return type.getHref();
};

/**
 * Return the rel attribute appropriate for this magic link node.
 *
 * @return {string} Either "mw:ExtLink" or "mw:WikiLink"
 */
ve.dm.MWMagicLinkNode.prototype.getRel = function () {
	var content = this.element.attributes.content,
		type = ve.dm.MWMagicLinkType.static.fromContent( content );
	return type.rel;
};

/**
 * Return the type of this magic link node: one of "RFC", "PMID", or "ISBN".
 *
 * @return {string} Magic link type
 */
ve.dm.MWMagicLinkNode.prototype.getMagicType = function () {
	var content = this.element.attributes.content,
		type = ve.dm.MWMagicLinkType.static.fromContent( content );
	return type.type;
};

/**
 * Return the numeric code associated with this magic link node.
 *
 * @return {string}
 */
ve.dm.MWMagicLinkNode.prototype.getCode = function () {
	var content = this.element.attributes.content,
		type = ve.dm.MWMagicLinkType.static.fromContent( content );
	return type.num;
};

/**
 * Encapsulation of a particular magic link type.
 *
 * @class
 * @private
 *
 * @constructor
 * @param {string} type
 *   The type of magic link; one of `'ISBN'`, `'PMID'`, or `'RFC'`.
 * @param {string} rel
 *   The value of the link's "rel" attribute.
 *   Either `'mw:ExtLink'` or `'mw:WikiLink'`.
 * @param {string} content
 *   The content of the magic link.
 */
ve.dm.MWMagicLinkType = function VeDmMWMagicLinkType( type, rel, content ) {
	this.type = type;
	this.rel = rel;
	this.content = content;
	// Make the code available as a property; this is also used for
	// validity checking.
	this.code = this.getCode();
};

OO.initClass( ve.dm.MWMagicLinkType );

/**
 * @inheritdoc ve.dm.MWMagicLinkNode#annotationFromContent
 */
ve.dm.MWMagicLinkType.prototype.getAnnotation = function () {
	return new ve.dm.MWExternalLinkAnnotation( {
		type: 'link/mwExternal',
		attributes: { href: this.getHref() }
	} );
};

/**
 * @inheritdoc ve.dm.MWMagicLinkNode#getCode
 * @protected
 */
ve.dm.MWMagicLinkType.prototype.getCode = function () {
	var m = /^([A-Z]+)[\t \u00A0\u1680\u2000-\u200A\u202F\u205F\u3000]+(\d+)$/.exec( this.content );
	if ( !m || m[ 1 ] !== this.type ) {
		return null;
	}
	return m[ 2 ];
};

/**
 * @method getHref
 * @inheritdoc ve.dm.MWMagicLinkNode#getHref
 */

/**
 * Return true if the given href is appropriate for this magic link.
 *
 * @param {string} href
 * @return {boolean}
 */
ve.dm.MWMagicLinkType.prototype.matchHref = function ( href ) {
	return href.replace( /^https?:/i, '' ) === this.getHref().replace( /^https?:/i, '' );
};

/**
 * Return the subclass of {@link ve.dm.MWMagicLinkType}
 * appropriate for the given content, or `null` if the content
 * is not appropriate for a magic link.
 *
 * @param {string} content
 * @return {ve.dm.MWMagicLinkType|null}
 */
ve.dm.MWMagicLinkType.static.fromContent = function ( content ) {
	var m = /^(ISBN|PMID|RFC)/.exec( content ),
		typeStr = m && m[ 1 ],
		type = null;
	if ( typeStr === 'ISBN' ) {
		type = new ve.dm.MWMagicLinkIsbnType( content );
	} else if ( typeStr === 'PMID' ) {
		type = new ve.dm.MWMagicLinkPmidType( content );
	} else if ( typeStr === 'RFC' ) {
		type = new ve.dm.MWMagicLinkRfcType( content );
	}
	// validate parsed number
	return type && type.code !== null ? type : null;
};

/**
 * An ISBN magic link.
 *
 * @class
 * @extends ve.dm.MWMagicLinkType
 * @private
 *
 * @constructor
 * @param {string} content
 */
ve.dm.MWMagicLinkIsbnType = function VeDmMWMagicLinkIsbnType( content ) {
	// Parent constructor
	ve.dm.MWMagicLinkIsbnType.super.call( this, 'ISBN', 'mw:WikiLink', content );
};

OO.inheritClass( ve.dm.MWMagicLinkIsbnType, ve.dm.MWMagicLinkType );

/**
 * @inheritdoc
 */
ve.dm.MWMagicLinkIsbnType.prototype.getAnnotation = function () {
	var conf = mw.config.get( 'wgVisualEditorConfig' ),
		title = mw.Title.newFromText( conf.specialBooksources + '/' + this.code );
	return ve.dm.MWInternalLinkAnnotation.static.newFromTitle( title );
};

/**
 * @inheritdoc
 */
ve.dm.MWMagicLinkIsbnType.prototype.getCode = function () {
	var content = this.content;

	if ( !/^ISBN[^-0-9][\s\S]+[0-9Xx]$/.test( content ) ) {
		return null;
	}

	// Remove unicode whitespace and dashes
	var spaceOrDash = /[-\t \u00A0\u1680\u2000-\u200A\u202F\u205F\u3000]+/g;
	var isbnCode = content.replace( spaceOrDash, '' ).replace( /^ISBN/, '' );

	// Verify format of ISBN
	if ( !/^(97[89])?\d{9}[0-9Xx]$/.test( isbnCode ) ) {
		return null;
	}
	return isbnCode.toUpperCase();
};

/**
 * @inheritdoc
 */
ve.dm.MWMagicLinkIsbnType.prototype.getHref = function () {
	var conf = mw.config.get( 'wgVisualEditorConfig' );
	return './' + conf.specialBooksources + '/' + this.code;
};

/**
 * @inheritdoc
 */
ve.dm.MWMagicLinkIsbnType.prototype.matchHref = function ( href ) {
	var conf = mw.config.get( 'wgVisualEditorConfig' ),
		matches = /^(?:[.]+\/)*([^/]+)\/(\d+[Xx]?)$/.exec( href );

	if ( !matches ) {
		return false;
	}
	// conf.specialBooksources has localized name for Special:Booksources
	var normalized = mw.libs.ve.decodeURIComponentIntoArticleTitle( matches[ 1 ], true ).replace( ' ', '_' );
	if ( normalized !== 'Special:BookSources' && normalized !== conf.specialBooksources ) {
		return false;
	}
	if ( matches[ 2 ] !== this.code ) {
		return false;
	}
	return true;
};

/**
 * A PMID magic link.
 *
 * @class
 * @extends ve.dm.MWMagicLinkType
 * @private
 *
 * @constructor
 * @param {string} content
 */
ve.dm.MWMagicLinkPmidType = function VeDmMWMagicLinkPmidType( content ) {
	// Parent constructor
	ve.dm.MWMagicLinkPmidType.super.call( this, 'PMID', 'mw:ExtLink', content );
};

OO.inheritClass( ve.dm.MWMagicLinkPmidType, ve.dm.MWMagicLinkType );

ve.dm.MWMagicLinkPmidType.prototype.getHref = function () {
	return mw.msg( 'pubmedurl', this.code );
};

/**
 * An RFC magic link.
 *
 * @class
 * @extends ve.dm.MWMagicLinkType
 * @private
 *
 * @constructor
 * @param {string} content
 */
ve.dm.MWMagicLinkRfcType = function VeDmMWMagicLinkRfcType( content ) {
	// Parent constructor
	ve.dm.MWMagicLinkRfcType.super.call( this, 'RFC', 'mw:ExtLink', content );
};

OO.inheritClass( ve.dm.MWMagicLinkRfcType, ve.dm.MWMagicLinkType );

ve.dm.MWMagicLinkRfcType.prototype.getHref = function () {
	return mw.msg( 'rfcurl', this.code );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWMagicLinkNode );
