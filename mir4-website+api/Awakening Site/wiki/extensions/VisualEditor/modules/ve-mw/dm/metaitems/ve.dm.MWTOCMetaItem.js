/*!
 * VisualEditor DataModel MWTOCDisableMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel TOC meta item (for __FORCETOC__ and __NOTOC__).
 *
 * @class
 * @extends ve.dm.MWFlaggedMetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MWTOCMetaItem = function VeDmMWTOCMetaItem() {
	// Parent constructor
	ve.dm.MWTOCMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWTOCMetaItem, ve.dm.MWFlaggedMetaItem );

/* Static Properties */

ve.dm.MWTOCMetaItem.static.name = 'mwTOC';

ve.dm.MWTOCMetaItem.static.group = 'mwTOC';

ve.dm.MWTOCMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/forcetoc', 'mw:PageProp/notoc' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWTOCMetaItem );
