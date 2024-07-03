/*!
 * VisualEditor DataModel MWTransclusionInlineNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki transclusion inline node.
 *
 * @class
 * @extends ve.dm.MWTransclusionNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWTransclusionInlineNode = function VeDmMWTransclusionInlineNode() {
	// Parent constructor
	ve.dm.MWTransclusionInlineNode.super.apply( this, arguments );
};

OO.inheritClass( ve.dm.MWTransclusionInlineNode, ve.dm.MWTransclusionNode );

/* Only ve.dm.MWTransclusionNode matches, then creates block/inline nodes dynamically */
ve.dm.MWTransclusionInlineNode.static.matchTagNames = [];

ve.dm.MWTransclusionInlineNode.static.name = 'mwTransclusionInline';

ve.dm.MWTransclusionInlineNode.static.isContent = true;

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWTransclusionInlineNode );
