/*!
 * VisualEditor ContentEditable MWHeadingNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MW heading node.
 *
 * @class
 * @extends ve.ce.HeadingNode
 * @constructor
 * @param {ve.dm.MWHeadingNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWHeadingNode = function VeCeMWHeadingNode() {
	// Parent constructor
	ve.ce.MWHeadingNode.super.apply( this, arguments );

	// Events
	this.model.connect( this, { update: 'onUpdate' } );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWHeadingNode, ve.ce.HeadingNode );

/* Static Properties */

ve.ce.MWHeadingNode.static.name = 'mwHeading';

/* Methods */

ve.ce.MWHeadingNode.prototype.onSetup = function () {
	// Parent method
	ve.ce.MWHeadingNode.super.prototype.onSetup.call( this );

	// Make reference to the surface
	this.surface = this.root && this.root.getSurface().getSurface();
	this.rebuildToc();
};

ve.ce.MWHeadingNode.prototype.onTeardown = function () {
	// Parent method
	ve.ce.MWHeadingNode.super.prototype.onTeardown.call( this );

	this.rebuildToc();
};

ve.ce.MWHeadingNode.prototype.onUpdate = function () {
	var surface = this.surface,
		node = this;

	// Parent method
	ve.ce.MWHeadingNode.super.prototype.onUpdate.call( this );

	if ( surface && surface.mwTocWidget ) {
		surface.getModel().getDocument().once( 'transact', function () {
			surface.mwTocWidget.updateNode( node );
		} );
	}
};

ve.ce.MWHeadingNode.prototype.rebuildToc = function () {
	var surface = this.surface;

	if ( surface && surface.mwTocWidget ) {
		surface.getModel().getDocument().once( 'transact', function () {
			surface.mwTocWidget.rebuild();
		} );
	}
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWHeadingNode );
