/*!
 * VisualEditor DataModel MWTransclusionTableCellNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki transclusion table cell node.
 *
 * @class
 * @extends ve.dm.MWTransclusionNode
 * @mixins ve.dm.TableCellableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWTransclusionTableCellNode = function VeDmMWTransclusionTableCellNode() {
	// Parent constructor
	ve.dm.MWTransclusionTableCellNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.TableCellableNode.call( this );
};

OO.inheritClass( ve.dm.MWTransclusionTableCellNode, ve.dm.MWTransclusionNode );

OO.mixinClass( ve.dm.MWTransclusionTableCellNode, ve.dm.TableCellableNode );

ve.dm.MWTransclusionTableCellNode.static.matchTagNames = [];

ve.dm.MWTransclusionTableCellNode.static.name = 'mwTransclusionTableCell';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWTransclusionTableCellNode );
