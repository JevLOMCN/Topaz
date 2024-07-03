/*!
 * VisualEditor ContentEditable MWInlineExtensionNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki inline extension node.
 *
 * @class
 * @abstract
 * @extends ve.ce.MWExtensionNode
 *
 * @constructor
 * @param {ve.dm.MWInlineExtensionNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWInlineExtensionNode = function VeCeMWInlineExtensionNode() {
	// Parent constructor
	ve.ce.MWInlineExtensionNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWInlineExtensionNode, ve.ce.MWExtensionNode );

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.MWInlineExtensionNode.prototype.onParseSuccess = function ( deferred, response ) {
	var data = response.visualeditor,
		contentNodes = $.parseHTML( data.content );

	// Inline nodes may come back in a wrapper paragraph; in that case, unwrap it
	if ( contentNodes.length === 1 && contentNodes[ 0 ].nodeName === 'P' ) {
		contentNodes = Array.prototype.slice.apply( contentNodes[ 0 ].childNodes );
	}
	deferred.resolve( contentNodes );
};
