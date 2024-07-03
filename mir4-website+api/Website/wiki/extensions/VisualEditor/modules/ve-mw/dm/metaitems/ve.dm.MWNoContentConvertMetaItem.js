/*!
 * VisualEditor DataModel MWNoContentConvertMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel disable content conversion meta item (for __NOCONTENTCONVERT__ and __NOCC__).
 *
 * @class
 * @extends ve.dm.MetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MWNoContentConvertMetaItem = function VeDmMWNoContentConvertMetaItem() {
	// Parent constructor
	ve.dm.MWNoContentConvertMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWNoContentConvertMetaItem, ve.dm.MetaItem );

/* Static Properties */

ve.dm.MWNoContentConvertMetaItem.static.name = 'mwNoContentConvert';

ve.dm.MWNoContentConvertMetaItem.static.group = 'mwNoContentConvert';

ve.dm.MWNoContentConvertMetaItem.static.matchTagNames = [ 'meta' ];

ve.dm.MWNoContentConvertMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/nocontentconvert', 'mw:PageProp/nocc' ];

ve.dm.MWNoContentConvertMetaItem.static.toDataElement = function ( domElements ) {
	// HACK: Don't rely on Parsoid always putting the RDFa type as a property
	return {
		type: this.name,
		originalProperty: domElements[ 0 ].getAttribute( 'property' )
	};
};

ve.dm.MWNoContentConvertMetaItem.static.toDomElements = function ( dataElement, doc ) {
	var meta = doc.createElement( 'meta' );
	meta.setAttribute(
		'property',
		( dataElement.attributes && dataElement.attributes.originalProperty ) || 'mw:PageProp/nocontentconvert'
	);
	return [ meta ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWNoContentConvertMetaItem );
