/*!
 * VisualEditor ContentEditable MWNumberedExternalLinkNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki numbered external link node.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @mixins ve.ce.FocusableNode
 * @constructor
 * @param {ve.dm.MWNumberedExternalLinkNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWNumberedExternalLinkNode = function VeCeMWNumberedExternalLinkNode() {
	// Parent constructor
	ve.ce.MWNumberedExternalLinkNode.super.apply( this, arguments );

	// Wrap the link in a span. The link must remain empty to match :empty selectors.
	// Also move ve-ce-* classes back up to the wrapper.
	this.$link = this.$element.removeClass( 've-ce-leafNode' )
		// These attributes are used for styling, so ensure they exists (they should
		// have been copied from originalDomElements already)
		.attr( 'rel', 'mw:ExtLink' )
		.addClass( 'external' );
	this.$element = $( '<span>' ).append( this.$link ).addClass( 've-ce-leafNode' );

	// Mixin constructors
	ve.ce.FocusableNode.call( this );

	// DOM changes
	this.$element
		// TODO: Test to see whether we can get away with adding unicode-bidi
		// embed/isolate style on $element. unicode-bidi isolate is more conceptually
		// correct, but not well supported (e.g. it seems to result in unexpected jumping
		// on Chromium).
		.addClass( 've-ce-mwNumberedExternalLinkNode' )
		// Need some content to make span take up a cursor position, but it must be text
		// with no directionality, else it can break Chromium cursoring (see
		// https://code.google.com/p/chromium/issues/detail?id=441056 ). Either a
		// unicorn-like img tag or the actual apparent link text ("[1]", hitherto shown
		// with CSS generated content) would fall foul of this bug. Use a zero-width
		// space so it doesn't change the appearance.
		.prepend( $( document.createTextNode( '\u200B' ) ) );

	// Events
	this.model.connect( this, { update: 'onUpdate' } );

	// Initialization
	this.onUpdate();
};

/* Inheritance */

OO.inheritClass( ve.ce.MWNumberedExternalLinkNode, ve.ce.LeafNode );

OO.mixinClass( ve.ce.MWNumberedExternalLinkNode, ve.ce.FocusableNode );

/* Static Properties */

ve.ce.MWNumberedExternalLinkNode.static.name = 'link/mwNumberedExternal';

ve.ce.MWNumberedExternalLinkNode.static.tagName = 'a';

ve.ce.MWNumberedExternalLinkNode.static.primaryCommandName = 'link';

/* Static Methods */

/**
 * @inheritdoc
 */
ve.ce.MWNumberedExternalLinkNode.static.getDescription = function ( model ) {
	return model.getAttribute( 'href' );
};

/* Methods */

/**
 * Handle model update events.
 */
ve.ce.MWNumberedExternalLinkNode.prototype.onUpdate = function () {
	// T322704
	ve.setAttributeSafe( this.$link[ 0 ], 'href', this.model.getAttribute( 'href' ) || '', '#' );
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWNumberedExternalLinkNode );
