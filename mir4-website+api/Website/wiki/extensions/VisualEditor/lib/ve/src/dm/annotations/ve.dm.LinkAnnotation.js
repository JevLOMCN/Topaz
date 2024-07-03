/*!
 * VisualEditor DataModel LinkAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel link annotation.
 *
 * Represents `<a>` tags that don't have a specific type.
 *
 * @class
 * @extends ve.dm.Annotation
 * @constructor
 * @param {Object} element
 */
ve.dm.LinkAnnotation = function VeDmLinkAnnotation() {
	// Parent constructor
	ve.dm.LinkAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.LinkAnnotation, ve.dm.Annotation );

/* Static Properties */

ve.dm.LinkAnnotation.static.name = 'link';

ve.dm.LinkAnnotation.static.matchTagNames = [ 'a' ];

ve.dm.LinkAnnotation.static.toDataElement = function ( domElements ) {
	if ( !domElements[ 0 ].hasAttribute( 'href' ) ) {
		return ve.dm.SpanAnnotation.static.toDataElement.apply( ve.dm.SpanAnnotation.static, arguments );
	}
	return {
		type: this.name,
		attributes: {
			href: domElements[ 0 ].getAttribute( 'href' )
		}
	};
};

ve.dm.LinkAnnotation.static.toDomElements = function ( dataElement, doc ) {
	var domElement = doc.createElement( 'a' );
	domElement.setAttribute( 'href', this.getHref( dataElement ) );
	return [ domElement ];
};

ve.dm.LinkAnnotation.static.describeChange = function ( key, change ) {
	if ( key === 'href' ) {
		var diff = this.getAttributeDiff( change.from, change.to );
		if ( diff ) {
			return ve.htmlMsg( 'visualeditor-changedesc-link-href-diff', diff );
		} else {
			return ve.htmlMsg( 'visualeditor-changedesc-link-href', this.wrapText( 'del', change.from ), this.wrapText( 'ins', change.to ) );
		}
	}
	// Parent method
	return ve.dm.LinkAnnotation.super.static.describeChange.apply( this, arguments );
};

/**
 * Get the link href from linear data. Helper function for toDomElements.
 *
 * Subclasses can override this if they provide complex href computation.
 *
 * @static
 * @inheritable
 * @param {Object} dataElement Linear model element
 * @return {string} Link href
 */
ve.dm.LinkAnnotation.static.getHref = function ( dataElement ) {
	return dataElement.attributes.href;
};

/* Methods */

/**
 * Convenience wrapper for .getHref() on the current element.
 *
 * @see #static-getHref
 * @return {string} Link href
 */
ve.dm.LinkAnnotation.prototype.getHref = function () {
	return this.constructor.static.getHref( this.element );
};

/**
 * Get the display title for this link
 *
 * Can be overridden by special link types.
 *
 * @return {string} Display title
 */
ve.dm.LinkAnnotation.prototype.getDisplayTitle = function () {
	return this.getHref();
};

/**
 * Get the fragment / hash for the current href
 *
 * @return {string|null} The fragment, or null if none is present
 */
ve.dm.LinkAnnotation.prototype.getFragment = function () {
	var href = this.getHref(),
		hashIndex = href.indexOf( '#' );
	if ( hashIndex === -1 ) {
		return null;
	}
	return href.slice( hashIndex + 1 );
};

/**
 * @inheritdoc
 */
ve.dm.LinkAnnotation.prototype.getComparableObject = function () {
	return {
		type: this.getType(),
		href: this.getAttribute( 'href' )
	};
};

/**
 * @inheritdoc
 */
ve.dm.LinkAnnotation.prototype.getComparableHtmlAttributes = function () {
	var comparableAttributes = ve.dm.LinkAnnotation.super.prototype.getComparableHtmlAttributes.call( this );
	delete comparableAttributes.href;
	return comparableAttributes;
};

ve.dm.LinkAnnotation.prototype.describeAdded = function () {
	return [ ve.msg( 'visualeditor-changedesc-link-added', this.getDisplayTitle() ) ];
};

ve.dm.LinkAnnotation.prototype.describeRemoved = function () {
	return [ ve.msg( 'visualeditor-changedesc-link-removed', this.getDisplayTitle() ) ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.LinkAnnotation );
