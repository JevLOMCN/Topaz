/*!
 * VisualEditor DataModel MetaItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel meta item.
 * TODO: rename to MetaNode to reflect the fact it is now a node
 *
 * @class
 * @abstract
 * @extends ve.dm.LeafNode
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MetaItem = function VeDmMetaItem() {
	// Parent constructor
	ve.dm.MetaItem.super.apply( this, arguments );
	// Mixin
	OO.EventEmitter.call( this );
	// Properties
	this.list = null;
};

/* Inheritance */

OO.inheritClass( ve.dm.MetaItem, ve.dm.LeafNode );

OO.mixinClass( ve.dm.MetaItem, OO.EventEmitter );

/* Static members */

ve.dm.MetaItem.static.isContent = false;

ve.dm.MetaItem.static.isMetaData = true;

ve.dm.MetaItem.static.canSerializeAsContent = true;

ve.dm.MetaItem.static.isDiffedAsLeaf = true;

/**
 * Symbolic name for the group this meta item type will be grouped in in ve.dm.MetaList.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.dm.MetaItem.static.group = 'misc';

/**
 * If the metaitem can be removed by regular remove operations
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.dm.MetaItem.static.removable = false;

/* Methods */

/**
 * Get the group this meta item belongs to.
 *
 * @see #static-group
 * @return {string} Group
 */
ve.dm.MetaItem.prototype.getGroup = function () {
	return this.constructor.static.group;
};
