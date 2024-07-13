/*!
 * VisualEditor DataModel MWIndexMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel index meta item (for __INDEX__ and __NOINDEX__).
 *
 * @class
 * @extends ve.dm.MWFlaggedMetaItem
 * @constructor
 * @param {Object} [element] Reference to element in meta-linmod
 */
ve.dm.MWIndexMetaItem = function VeDmMWIndexMetaItem() {
	// Parent constructor
	ve.dm.MWIndexMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWIndexMetaItem, ve.dm.MWFlaggedMetaItem );

/* Static Properties */

ve.dm.MWIndexMetaItem.static.name = 'mwIndex';

ve.dm.MWIndexMetaItem.static.group = 'mwIndex';

ve.dm.MWIndexMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/index', 'mw:PageProp/noindex' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWIndexMetaItem );
