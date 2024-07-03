/*!
 * VisualEditor DataModel TableSlice class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel document slice
 *
 * @class
 * @extends ve.dm.Document
 * @constructor
 * @param {HTMLDocument|Array|ve.dm.ElementLinearData} data
 * @param {HTMLDocument} [htmlDocument]
 * @param {ve.dm.Document} [parentDocument]
 * @param {ve.dm.InternalList} [internalList]
 * @param {ve.Range} [tableRange] Table range
 * @param {ve.dm.Document} [originalDocument]
 */
ve.dm.TableSlice = function VeDmTableSlice( data, htmlDocument, parentDocument, internalList, tableRange, originalDocument ) {
	// Parent constructor
	ve.dm.TableSlice.super.call( this, data, htmlDocument, parentDocument, internalList, tableRange, tableRange, originalDocument );
};

/* Inheritance */

OO.inheritClass( ve.dm.TableSlice, ve.dm.DocumentSlice );

/* Methods */

/**
 * Get the parent table node in this document slice
 *
 * @return {ve.dm.TableNode} Table node
 */
ve.dm.TableSlice.prototype.getTableNode = function () {
	if ( !this.documentNode.length ) {
		this.rebuildTree();
	}
	return this.documentNode.children[ 0 ];
};
