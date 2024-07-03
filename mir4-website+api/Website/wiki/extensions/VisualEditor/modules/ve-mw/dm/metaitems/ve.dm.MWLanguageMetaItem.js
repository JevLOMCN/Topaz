/*!
 * VisualEditor DataModel MWLanguageMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel language meta item.
 *
 * @class
 * @extends ve.dm.MetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MWLanguageMetaItem = function VeDmMWLanguageMetaItem() {
	// Parent constructor
	ve.dm.MWLanguageMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWLanguageMetaItem, ve.dm.MetaItem );

/* Static Properties */

ve.dm.MWLanguageMetaItem.static.name = 'mwLanguage';

ve.dm.MWLanguageMetaItem.static.matchTagNames = [ 'link' ];

ve.dm.MWLanguageMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/Language' ];

ve.dm.MWLanguageMetaItem.static.toDataElement = function ( domElements ) {
	var href = domElements[ 0 ].getAttribute( 'href' );
	return {
		type: this.name,
		attributes: {
			href: href
		}
	};
};

ve.dm.MWLanguageMetaItem.static.toDomElements = function ( dataElement, doc ) {
	var domElement = doc.createElement( 'link' );
	domElement.setAttribute( 'rel', 'mw:PageProp/Language' );
	domElement.setAttribute( 'href', dataElement.attributes.href );
	return [ domElement ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWLanguageMetaItem );
