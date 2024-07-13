/*!
 * VisualEditor DataModel MWSignatureNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki signature node. This defines the behavior of the data model for the
 * signature, especially the fact that it needs to be converted into a wikitext signature on
 * save.
 *
 * @class
 * @extends ve.dm.LeafNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.MWSignatureNode = function VeDmMWSignatureNode() {
	// Parent constructor
	ve.dm.MWSignatureNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.GeneratedContentNode.call( this );
	ve.dm.FocusableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWSignatureNode, ve.dm.LeafNode );
OO.mixinClass( ve.dm.MWSignatureNode, ve.dm.GeneratedContentNode );
OO.mixinClass( ve.dm.MWSignatureNode, ve.dm.FocusableNode );

/* Static members */

ve.dm.MWSignatureNode.static.name = 'mwSignature';

ve.dm.MWSignatureNode.static.isContent = true;

ve.dm.MWSignatureNode.static.matchTagNames = null;

ve.dm.MWSignatureNode.static.matchRdfaTypes = [];

ve.dm.MWSignatureNode.static.matchFunction = function () {
	return false;
};

ve.dm.MWSignatureNode.static.getTemplateDataElement = function () {
	return {
		type: 'mwTransclusionInline',
		attributes: {
			mw: {
				parts: [ '~~~~' ]
			}
		}
	};
};

ve.dm.MWSignatureNode.static.toDomElements = function ( dataElement, doc, converter ) {
	// Ignore the mwSignature dataElement and create a wikitext transclusion
	dataElement = this.getTemplateDataElement();
	return ve.dm.MWTransclusionInlineNode.static.toDomElements( dataElement, doc, converter );
};

// Can't be generated from existing HTML documents, this method should never be called
ve.dm.MWSignatureNode.static.toDataElement = null;

// In previews we look up the rendering of the generated mwTransclusionInline node,
// so use that node's hash object.
ve.dm.MWSignatureNode.static.getHashObjectForRendering = function () {
	return ve.dm.MWTransclusionNode.static.getHashObject( this.getTemplateDataElement() );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWSignatureNode );
