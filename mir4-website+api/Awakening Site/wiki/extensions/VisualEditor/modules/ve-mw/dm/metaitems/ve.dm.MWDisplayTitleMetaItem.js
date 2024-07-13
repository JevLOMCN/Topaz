/*!
 * VisualEditor DataModel MWDisplayTitleMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel display title meta item.
 *
 * @class
 * @extends ve.dm.MetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MWDisplayTitleMetaItem = function VeDmMWDisplayTitleMetaItem() {
	// Parent constructor
	ve.dm.MWDisplayTitleMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWDisplayTitleMetaItem, ve.dm.MetaItem );

/* Static Properties */

ve.dm.MWDisplayTitleMetaItem.static.name = 'mwDisplayTitle';

ve.dm.MWDisplayTitleMetaItem.static.group = 'mwDisplayTitle';

ve.dm.MWDisplayTitleMetaItem.static.matchTagNames = [ 'meta' ];

ve.dm.MWDisplayTitleMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/displaytitle' ];

ve.dm.MWDisplayTitleMetaItem.static.toDataElement = function ( domElements ) {
	var content = domElements[ 0 ].getAttribute( 'content' );
	return {
		type: this.name,
		attributes: {
			content: content
		}
	};
};

ve.dm.MWDisplayTitleMetaItem.static.toDomElements = function ( dataElement, doc ) {
	var meta = doc.createElement( 'meta' );
	meta.setAttribute( 'property', 'mw:PageProp/displaytitle' );
	meta.setAttribute( 'content', dataElement.attributes.content );
	return [ meta ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWDisplayTitleMetaItem );
