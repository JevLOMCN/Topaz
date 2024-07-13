/*!
 * VisualEditor DataModel MWNumberedExternalLinkNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki numbered external link node.
 *
 * @class
 * @extends ve.dm.LeafNode
 * @mixins ve.dm.FocusableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWNumberedExternalLinkNode = function VeDmMWNumberedExternalLinkNode() {
	// Parent constructor
	ve.dm.MWNumberedExternalLinkNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.FocusableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWNumberedExternalLinkNode, ve.dm.LeafNode );

OO.mixinClass( ve.dm.MWNumberedExternalLinkNode, ve.dm.FocusableNode );

/* Static Properties */

ve.dm.MWNumberedExternalLinkNode.static.name = 'link/mwNumberedExternal';

ve.dm.MWNumberedExternalLinkNode.static.isContent = true;

ve.dm.MWNumberedExternalLinkNode.static.matchTagNames = [ 'a' ];

ve.dm.MWNumberedExternalLinkNode.static.matchRdfaTypes = [ 'mw:ExtLink', 've:NumberedLink' ];

// Allow additional 'rel' values in Parsoid output (T321437)
ve.dm.MWNumberedExternalLinkNode.static.allowedRdfaTypes = [ 'nofollow', 'noreferrer', 'noopener' ];

ve.dm.MWNumberedExternalLinkNode.static.disallowedAnnotationTypes = [ 'link' ];

ve.dm.MWNumberedExternalLinkNode.static.matchFunction = function ( domElement ) {
	// Must be empty, or explicitly flagged as a numbered link. We can't just
	// rely on emptiness, because we give the link content for cross-document
	// pastes so it won't be pruned. (And so it'll be functional in non-wiki
	// contexts.)
	// Note that ve:NumberedLink is only used internally by VE for cross-document
	// pastes and is never sent to Parsoid.
	return domElement.childNodes.length === 0 || domElement.getAttribute( 'rel' ).indexOf( 've:NumberedLink' ) !== -1;
};

ve.dm.MWNumberedExternalLinkNode.static.toDataElement = function ( domElements ) {
	return {
		type: this.name,
		attributes: {
			href: domElements[ 0 ].getAttribute( 'href' )
		}
	};
};

ve.dm.MWNumberedExternalLinkNode.static.toDomElements = function ( dataElement, doc, converter ) {
	var node = this,
		domElement = doc.createElement( 'a' );

	domElement.setAttribute( 'href', dataElement.attributes.href );
	domElement.setAttribute( 'rel', 'mw:ExtLink' );

	// Ensure there is a text version of the counter in the clipboard
	// as external documents may not have the same stylesheet - and Firefox
	// discards empty tags on copy.
	if ( converter.isForClipboard() ) {
		var counter = 1;
		var offset = converter.documentData.indexOf( dataElement );

		if ( offset !== -1 ) {
			converter.documentData.slice( 0, offset ).forEach( function ( el ) {
				if ( el.type && el.type === node.name ) {
					counter++;
				}
			} );
		}
		domElement.appendChild( doc.createTextNode( '[' + counter + ']' ) );
		// Explicitly mark as a numbered link as the node is no longer empty.
		domElement.setAttribute( 'rel', 've:NumberedLink' );
	}
	return [ domElement ];
};

/* Methods */

/**
 * Convenience wrapper for .getHref() on the current element.
 *
 * @return {string} Link href
 */
ve.dm.MWNumberedExternalLinkNode.prototype.getHref = function () {
	return this.element.attributes.href;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWNumberedExternalLinkNode );
