/*!
 * VisualEditor ContentEditable MWTransclusionInlineNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki transclusion inline node.
 *
 * @class
 * @extends ve.ce.MWTransclusionNode
 * @constructor
 * @param {ve.dm.MWTransclusionInlineNode} model Model to observe
 */
ve.ce.MWTransclusionInlineNode = function VeCeMWTransclusionInlineNode( model ) {
	// Parent constructor
	ve.ce.MWTransclusionInlineNode.super.call( this, model );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWTransclusionInlineNode, ve.ce.MWTransclusionNode );

/* Static Properties */

ve.ce.MWTransclusionInlineNode.static.name = 'mwTransclusionInline';

ve.ce.MWTransclusionInlineNode.static.tagName = 'span';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWTransclusionInlineNode );
