/**
 * PingNode model. A ping is just a link to a user page, but
 * by defining it as a node we can make is a single FocusableNode.
 *
 * @class
 * @extends ve.dm.LeafNode
 * @mixes ve.dm.FocusableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
function DmMWPingNode() {
	// Parent constructor
	DmMWPingNode.super.apply( this, arguments );

	// Mixin constructor
	ve.dm.FocusableNode.call( this );
}

/* Inheritance */

OO.inheritClass( DmMWPingNode, ve.dm.LeafNode );

OO.mixinClass( DmMWPingNode, ve.dm.FocusableNode );

/* Static members */

DmMWPingNode.static.name = 'mwPing';

DmMWPingNode.static.isContent = true;

DmMWPingNode.static.matchTagNames = null;

DmMWPingNode.static.matchRdfaTypes = [];

DmMWPingNode.static.matchFunction = function () {
	return false;
};

DmMWPingNode.static.disallowedAnnotationTypes = [ 'link' ];

DmMWPingNode.static.toDomElements = function ( dataElement, doc, converter ) {
	var domElements,
		prefix = mw.msg( 'discussiontools-replywidget-mention-prefix' ),
		suffix = mw.msg( 'discussiontools-replywidget-mention-suffix' ),
		title = mw.Title.makeTitle( mw.config.get( 'wgNamespaceIds' ).user, dataElement.attributes.user );

	dataElement = ve.dm.MWInternalLinkAnnotation.static.dataElementFromTitle( title );
	domElements = ve.dm.MWInternalLinkAnnotation.static.toDomElements( dataElement, doc, converter );
	domElements[ 0 ].appendChild(
		doc.createTextNode( title.getMainText() )
	);
	domElements.unshift( document.createTextNode( prefix ) );
	domElements.push( document.createTextNode( suffix ) );

	return domElements;
};

// toDataElement should never be called for this node
DmMWPingNode.static.toDataElement = null;

/* Registration */

ve.dm.modelRegistry.register( DmMWPingNode );
