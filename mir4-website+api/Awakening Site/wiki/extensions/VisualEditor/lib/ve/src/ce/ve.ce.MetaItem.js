/*!
 * VisualEditor ContentEditable MetaItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable meta item node.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @constructor
 * @param {ve.dm.MetaItem} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MetaItem = function VeCeMetaItem( model, config ) {
	// Call parent constructor with explicit DOM node-less $element
	ve.ce.MetaItem.super.call( this, model, ve.extendObject( {}, config, { $element: $() } ) );
};

/* Inheritance */

OO.inheritClass( ve.ce.MetaItem, ve.ce.LeafNode );

/* Static Properties */

ve.ce.MetaItem.static.name = 'meta';

ve.ce.nodeFactory.register( ve.ce.MetaItem );
