/*!
 * VisualEditor DataModel CommentMetaItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class
 * @extends ve.dm.MetaItem
 *
 * @constructor
 * @param {Object} element Reference to element in linmod
 */
ve.dm.CommentMetaItem = function VeDmCommentMetaItem() {
	// Parent constructor
	ve.dm.CommentMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.CommentMetaItem, ve.dm.MetaItem );

/* Static Properties */

ve.dm.CommentMetaItem.static.name = 'commentMeta';

ve.dm.CommentMetaItem.static.matchTagNames = [];

ve.dm.CommentMetaItem.static.preserveHtmlAttributes = false;

ve.dm.CommentMetaItem.static.toDomElements = function ( dataElement, doc ) {
	return [ doc.createComment( dataElement.attributes.text ) ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.CommentMetaItem );
