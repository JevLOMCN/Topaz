/*!
 * VisualEditor DataModel RemovableAlienMetaItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel removable alien meta item.
 *
 * @class
 * @extends ve.dm.AlienMetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.RemovableAlienMetaItem = function VeDmRemovableAlienMetaItem() {
	// Parent constructor
	ve.dm.RemovableAlienMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.RemovableAlienMetaItem, ve.dm.AlienMetaItem );

/* Static Properties */

ve.dm.RemovableAlienMetaItem.static.name = 'removableAlienMeta';

ve.dm.RemovableAlienMetaItem.static.matchTagNames = [];

ve.dm.RemovableAlienMetaItem.static.removable = true;

/* Registration */

ve.dm.modelRegistry.register( ve.dm.RemovableAlienMetaItem );
