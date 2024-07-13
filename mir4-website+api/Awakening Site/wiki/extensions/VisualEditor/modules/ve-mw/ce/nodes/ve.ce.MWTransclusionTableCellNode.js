/*!
 * VisualEditor ContentEditable MWTransclusionTableCellNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki transclusion table cell node.
 *
 * @class
 * @extends ve.ce.MWTransclusionNode
 * @constructor
 * @mixins ve.ce.TableCellableNode
 * @param {ve.dm.MWTransclusionTableCellNode} model Model to observe
 */
ve.ce.MWTransclusionTableCellNode = function VeCeMWTransclusionTableCellNode( model ) {
	// Parent constructor
	ve.ce.MWTransclusionTableCellNode.super.call( this, model );

	// Mixin constructors
	ve.ce.TableCellableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWTransclusionTableCellNode, ve.ce.MWTransclusionNode );

OO.mixinClass( ve.ce.MWTransclusionTableCellNode, ve.ce.TableCellableNode );

/* Static Properties */

ve.ce.MWTransclusionTableCellNode.static.name = 'mwTransclusionTableCell';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWTransclusionTableCellNode );
