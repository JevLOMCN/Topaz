/*!
 * VisualEditor ContentEditable TableCaptionNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable table caption node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @mixins ve.ce.ActiveNode
 * @constructor
 * @param {ve.dm.TableCaptionNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.TableCaptionNode = function VeCeTableCaptionNode() {
	// Parent constructor
	ve.ce.TableCaptionNode.super.apply( this, arguments );

	// Mixin constructor
	ve.ce.ActiveNode.call( this );

	// DOM changes
	this.$element.addClass( 've-ce-tableCaptionNode' );
};

/* Inheritance */

OO.inheritClass( ve.ce.TableCaptionNode, ve.ce.BranchNode );

OO.mixinClass( ve.ce.TableCaptionNode, ve.ce.ActiveNode );

/* Static Properties */

ve.ce.TableCaptionNode.static.name = 'tableCaption';

ve.ce.TableCaptionNode.static.tagName = 'caption';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.TableCaptionNode );
