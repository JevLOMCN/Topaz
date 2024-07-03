/*!
 * VisualEditor ContentEditable MWAlienAnnotationNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki alien annotation node.
 *
 * @class
 * @abstract
 * @extends ve.ce.MWAnnotationNode
 *
 * @constructor
 * @param {ve.dm.MWAlienAnnotationNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWAlienAnnotationNode = function VeCeMWAlienAnnotationNode() {
	// Parent constructor
	ve.ce.MWAlienAnnotationNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWAlienAnnotationNode, ve.ce.MWAnnotationNode );

/* Static members */

ve.ce.MWAlienAnnotationNode.static.name = 'mwAlienAnnotation';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWAlienAnnotationNode );
