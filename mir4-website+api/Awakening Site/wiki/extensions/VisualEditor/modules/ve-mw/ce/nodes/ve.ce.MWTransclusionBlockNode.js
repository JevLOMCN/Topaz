/*!
 * VisualEditor ContentEditable MWTransclusionBlockNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki transclusion block node.
 *
 * @class
 * @extends ve.ce.MWTransclusionNode
 * @constructor
 * @param {ve.dm.MWTransclusionBlockNode} model Model to observe
 */
ve.ce.MWTransclusionBlockNode = function VeCeMWTransclusionBlockNode( model ) {
	// Parent constructor
	ve.ce.MWTransclusionBlockNode.super.call( this, model );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWTransclusionBlockNode, ve.ce.MWTransclusionNode );

/* Static Properties */

ve.ce.MWTransclusionBlockNode.static.name = 'mwTransclusionBlock';

ve.ce.MWTransclusionBlockNode.static.tagName = 'div';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWTransclusionBlockNode );
