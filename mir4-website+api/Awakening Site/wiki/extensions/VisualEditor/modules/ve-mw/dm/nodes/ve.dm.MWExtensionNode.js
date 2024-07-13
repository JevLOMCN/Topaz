/*!
 * VisualEditor DataModel MWExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki extension node.
 *
 * @class
 * @abstract
 * @extends ve.dm.LeafNode
 * @mixins ve.dm.FocusableNode
 * @mixins ve.dm.GeneratedContentNode
 *
 * @constructor
 */
ve.dm.MWExtensionNode = function VeDmMWExtensionNode() {
	// Parent constructor
	ve.dm.MWExtensionNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.GeneratedContentNode.call( this );
	ve.dm.FocusableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWExtensionNode, ve.dm.LeafNode );
OO.mixinClass( ve.dm.MWExtensionNode, ve.dm.FocusableNode );
OO.mixinClass( ve.dm.MWExtensionNode, ve.dm.GeneratedContentNode );

/* Static members */

ve.dm.MWExtensionNode.static.enableAboutGrouping = true;

ve.dm.MWExtensionNode.static.matchTagNames = null;

ve.dm.MWExtensionNode.static.childNodeTypes = [];

/**
 * HTML tag name.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.dm.MWExtensionNode.static.tagName = null;

/**
 * Name of the MediaWiki parser extension tag. (Not related to the name of the MediaWiki extension.)
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.dm.MWExtensionNode.static.extensionName = null;

ve.dm.MWExtensionNode.static.getMatchRdfaTypes = function () {
	return [ 'mw:Extension/' + this.extensionName ];
};

/**
 * @inheritdoc
 * @param {Node[]} domElements
 * @param {ve.dm.Converter} converter
 * @param {string} [type] Type to give dataElement, defaults to static.name
 */
ve.dm.MWExtensionNode.static.toDataElement = function ( domElements, converter, type ) {
	var mwDataJSON = domElements[ 0 ].getAttribute( 'data-mw' ),
		mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {};

	var dataElement = {
		type: type || this.name,
		attributes: {
			mw: mwData,
			originalMw: mwDataJSON
		}
	};

	this.storeGeneratedContents( dataElement, domElements, converter.getStore() );
	// Sub-classes should not modify dataElement beyond this point as it will invalidate the cache

	return dataElement;
};

/**
 * @inheritdoc ve.dm.Node
 */
ve.dm.MWExtensionNode.static.cloneElement = function () {
	// Parent method
	var clone = ve.dm.MWExtensionNode.super.static.cloneElement.apply( this, arguments );
	delete clone.attributes.originalMw;
	return clone;
};

ve.dm.MWExtensionNode.static.toDomElements = function ( dataElement, doc, converter ) {
	var originalMw = dataElement.attributes.originalMw;

	var els;
	// If the transclusion is unchanged just send back the
	// original DOM elements so selser can skip over it
	if (
		dataElement.originalDomElementsHash &&
		originalMw && ve.compare( dataElement.attributes.mw, JSON.parse( originalMw ) )
	) {
		// originalDomElements is also used for CE rendering so return a copy
		els = ve.copyDomElements( converter.getStore().value( dataElement.originalDomElementsHash ), doc );
	} else {
		var store = converter.getStore();
		var value;
		if (
			converter.doesModeNeedRendering() &&
			// Use getHashObjectForRendering to get the rendering from the store
			( value = store.value( store.hashOfValue( null, OO.getHash( [ this.getHashObjectForRendering( dataElement ), undefined ] ) ) ) )
		) {
			// For the clipboard use the current DOM contents so the user has something
			// meaningful to paste into external applications
			els = ve.copyDomElements( value, doc );
		} else {
			var el = doc.createElement( this.tagName );
			el.setAttribute( 'typeof', 'mw:Extension/' + this.getExtensionName( dataElement ) );
			el.setAttribute( 'data-mw', JSON.stringify( dataElement.attributes.mw ) );
			els = [ el ];
		}
	}
	return els;
};

ve.dm.MWExtensionNode.static.getHashObject = function ( dataElement ) {
	return {
		type: dataElement.type,
		mw: ve.copy( dataElement.attributes.mw )
	};
};

/**
 * Get name of the MediaWiki parser extension tag.
 *
 * Static version for toDomElements
 *
 * @static
 * @param {Object} dataElement Data element
 * @return {string} Extension name
 */
ve.dm.MWExtensionNode.static.getExtensionName = function () {
	return this.extensionName;
};

ve.dm.MWExtensionNode.static.describeChanges = function ( attributeChanges, attributes, element ) {
	var descriptions = [],
		fromBody = attributeChanges.mw.from.body,
		toBody = attributeChanges.mw.to.body;

	if ( attributeChanges.mw ) {
		// HACK: Try to generate an '<Extension> has changed' message using the associated tool's title
		var tools = ve.ui.toolFactory.getRelatedItems( [ ve.dm.nodeFactory.createFromElement( element ) ] );
		if ( tools.length ) {
			descriptions.push( ve.msg( 'visualeditor-changedesc-unknown',
				OO.ui.resolveMsg( ve.ui.toolFactory.lookup( tools[ 0 ].name ).static.title )
			) );
		}
		// Compare body - default behaviour in #describeChange does nothing
		if ( !ve.compare( fromBody, toBody ) ) {
			var change = this.describeChange( 'body', {
				from: fromBody && fromBody.extsrc,
				to: toBody && toBody.extsrc
			} );
			if ( change ) {
				descriptions.push( change );
			}
		}
		// Append attribute changes
		// Parent method
		Array.prototype.push.apply( descriptions, ve.dm.MWExtensionNode.super.static.describeChanges.call(
			this,
			ve.ui.DiffElement.static.compareAttributes( attributeChanges.mw.from.attrs || {}, attributeChanges.mw.to.attrs || {} ),
			attributes
		) );
		return descriptions;
	}
	// 'mw' should be the only attribute that changes...
	return [];
};

ve.dm.MWExtensionNode.static.describeChange = function ( key ) {
	if ( key === 'body' ) {
		// TODO: Produce a diff of the body, suitable to display in the sidebar.
		return null;
	}
	// Parent method
	return ve.dm.MWExtensionNode.super.static.describeChange.apply( this, arguments );
};

/* Methods */

/**
 * Get name of the MediaWiki parser extension tag.
 *
 * @return {string} Extension name
 */
ve.dm.MWExtensionNode.prototype.getExtensionName = function () {
	return this.constructor.static.getExtensionName( this.element );
};
