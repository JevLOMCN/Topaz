/*!
 * VisualEditor DataModel MWNewSectionEditMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel new section edit link meta item (for __NEWSECTIONLINK__ and __NONEWSECTIONLINK__).
 *
 * @class
 * @extends ve.dm.MWFlaggedMetaItem
 * @constructor
 * @param {Object} [element] Reference to element in meta-linmod
 */
ve.dm.MWNewSectionEditMetaItem = function VeDmMWNewSectionEditMetaItem() {
	// Parent constructor
	ve.dm.MWNewSectionEditMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWNewSectionEditMetaItem, ve.dm.MWFlaggedMetaItem );

/* Static Properties */

ve.dm.MWNewSectionEditMetaItem.static.name = 'mwNewSectionEdit';

ve.dm.MWNewSectionEditMetaItem.static.group = 'mwNewSectionEdit';

ve.dm.MWNewSectionEditMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/newsectionlink', 'mw:PageProp/nonewsectionlink' ];

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWNewSectionEditMetaItem );
