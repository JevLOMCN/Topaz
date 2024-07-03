/*!
 * VisualEditor DataModel MWNoTitleConvertMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel disable title conversion meta item (for __NOTITILECONVERT__ and __NOTC__).
 *
 * @class
 * @extends ve.dm.MetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MWNoTitleConvertMetaItem = function VeDmMWNoTitleConvertMetaItem() {
	// Parent constructor
	ve.dm.MWNoTitleConvertMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWNoTitleConvertMetaItem, ve.dm.MetaItem );

/* Static Properties */

ve.dm.MWNoTitleConvertMetaItem.static.name = 'mwNoTitleConvert';

ve.dm.MWNoTitleConvertMetaItem.static.group = 'mwNoTitleConvert';

ve.dm.MWNoTitleConvertMetaItem.static.matchTagNames = [ 'meta' ];

ve.dm.MWNoTitleConvertMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/notitleconvert', 'mw:PageProp/notc' ];

ve.dm.MWNoTitleConvertMetaItem.static.toDataElement = function ( domElements ) {
	// HACK: Don't rely on Parsoid always putting the RDFa type as a property
	return {
		type: this.name,
		originalProperty: domElements[ 0 ].getAttribute( 'property' )
	};
};

ve.dm.MWNoTitleConvertMetaItem.static.toDomElements = function ( dataElement, doc ) {
	var meta = doc.createElement( 'meta' );
	meta.setAttribute(
		'property',
		( dataElement.attributes && dataElement.attributes.originalProperty ) || 'mw:PageProp/notitleconvert'
	);
	return [ meta ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWNoTitleConvertMetaItem );
