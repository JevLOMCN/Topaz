/*!
 * VisualEditor DataModel TableNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel table node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.TableNode = function VeDmTableNode() {
	// Parent constructor
	ve.dm.TableNode.super.apply( this, arguments );

	// A dense representation of the sparse model to make manipulations
	// in presence of spanning cells feasible.
	this.matrix = new ve.dm.TableMatrix( this );

	// Events
	this.connect( this, { splice: 'onSplice' } );
};

/* Inheritance */

OO.inheritClass( ve.dm.TableNode, ve.dm.BranchNode );

/**
 * @event cellAttributeChange
 * @param {ve.dm.TableCellableNode} cell
 */

/* Static Properties */

ve.dm.TableNode.static.name = 'table';

ve.dm.TableNode.static.childNodeTypes = [ 'tableSection', 'tableCaption' ];

ve.dm.TableNode.static.matchTagNames = [ 'table' ];

/* Methods */

/**
 * Handle splicing of child nodes
 */
ve.dm.TableNode.prototype.onSplice = function () {
	var nodes = Array.prototype.slice.call( arguments, 2 );
	this.getMatrix().invalidate();
	for ( var i = 0; i < nodes.length; i++ ) {
		nodes[ i ].connect( this, {
			cellAttributeChange: 'onCellAttributeChange'
		} );
	}
};

/**
 * Handle cell attribute changes
 *
 * @param {ve.dm.TableCellableNode} cell
 * @fires cellAttributeChange
 */
ve.dm.TableNode.prototype.onCellAttributeChange = function ( cell ) {
	this.emit( 'cellAttributeChange', cell );
};

/**
 * Get table matrix for this table node
 *
 * @return {ve.dm.TableMatrix} Table matrix
 */
ve.dm.TableNode.prototype.getMatrix = function () {
	return this.matrix;
};

/**
 * Get the table's caption node, if it exists
 *
 * @return {ve.dm.TableCaptionNode|null} The table's caption node, or null if not found
 */
ve.dm.TableNode.prototype.getCaptionNode = function () {
	for ( var i = 0, l = this.children.length; i < l; i++ ) {
		if ( this.children[ i ] instanceof ve.dm.TableCaptionNode ) {
			return this.children[ i ];
		}
	}
	return null;
};

/**
 * Provides a cell iterator that allows convenient traversal regardless of
 * the structure with respect to sections.
 *
 * @return {ve.dm.TableNodeCellIterator}
 */
ve.dm.TableNode.prototype.getIterator = function () {
	return new ve.dm.TableNodeCellIterator( this );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.TableNode );

/**
 * A helper class to iterate over the cells of a table node.
 *
 * It provides a unified interface to iterate cells in presence of table sections,
 * e.g., providing consecutive row indexes.
 *
 * @class
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {ve.dm.TableNode} tableNode Table node to iterate through
 */
ve.dm.TableNodeCellIterator = function VeDmTableNodeCellIterator( tableNode ) {
	// Mixin constructors
	OO.EventEmitter.call( this );

	this.table = tableNode;

	this.sectionIndex = 0;
	this.rowIndex = 0;
	this.cellIndex = 0;

	this.sectionCount = this.table.children.length;
	this.rowCount = 0;
	this.cellCount = 0;

	this.sectionNode = null;
	this.rowNode = null;
	this.cellNode = null;

	this.finished = false;
};

/* Inheritance */

OO.mixinClass( ve.dm.TableNodeCellIterator, OO.EventEmitter );

/* Events */

/**
 * @event newSection
 * @param {ve.dm.TableSectionNode} node Table section node
 */

/**
 * @event newRow
 * @param {ve.dm.TableRowNode} node Table row node
 */

/* Methods */

/**
 * Check if the iterator has finished iterating over the cells of a table node.
 *
 * @return {boolean} Iterator is finished
 */
ve.dm.TableNodeCellIterator.prototype.isFinished = function () {
	return this.finished;
};

/**
 * Get the next cell node
 *
 * @return {ve.dm.TableCellNode|null|undefined} Next cell node, null if a not a table cell, or undefined if at the end
 * @throws {Error} TableNodeCellIterator has no more cells left.
 */
ve.dm.TableNodeCellIterator.prototype.next = function () {
	if ( this.isFinished() ) {
		throw new Error( 'TableNodeCellIterator has no more cells left.' );
	}
	this.nextCell( this );
	return this.cellNode;
};

/**
 * Move to the next table section
 *
 * @fires newSection
 */
ve.dm.TableNodeCellIterator.prototype.nextSection = function () {
	// If there are no sections left, finish
	if ( this.sectionIndex >= this.sectionCount ) {
		this.finished = true;
		this.sectionNode = undefined;
		return;
	}
	// Get the next node and make sure it's a section node (and not an alien node)
	var sectionNode = this.table.children[ this.sectionIndex ];
	this.sectionIndex++;
	this.rowIndex = 0;
	if ( sectionNode instanceof ve.dm.TableSectionNode ) {
		this.sectionNode = sectionNode;
		this.rowCount = this.sectionNode.children.length;
		this.emit( 'newSection', this.sectionNode );
	} else {
		this.nextSection();
		return;
	}
};

/**
 * Move to the next table row
 *
 * @fires newRow
 */
ve.dm.TableNodeCellIterator.prototype.nextRow = function () {
	// If there are no rows left, go to the next section
	if ( this.rowIndex >= this.rowCount ) {
		this.nextSection();
		if ( this.isFinished() ) {
			this.rowNode = undefined;
			return;
		}
	}
	// Get the next node and make sure it's a row node (and not an alien node)
	var rowNode = this.sectionNode.children[ this.rowIndex ];
	this.rowIndex++;
	this.cellIndex = 0;
	if ( rowNode instanceof ve.dm.TableRowNode ) {
		this.rowNode = rowNode;
		this.cellCount = this.rowNode.children.length;
		this.emit( 'newRow', this.rowNode );
	} else {
		this.nextRow();
		return;
	}
};

/**
 * Move to the next table cell
 */
ve.dm.TableNodeCellIterator.prototype.nextCell = function () {
	// For the first read, sectionNode and rowNode will be empty
	if ( !this.sectionNode ) {
		this.nextSection();
	}
	if ( !this.rowNode ) {
		this.nextRow();
	}
	// If there are no cells left, go to the next row
	if ( this.cellIndex >= this.cellCount ) {
		this.nextRow();
		// If calling next row finished the iterator, clear and return
		if ( this.isFinished() ) {
			this.cellNode = undefined;
			return;
		}
	}
	// Get the next node and make sure it's a cell node (and not an alien node)
	var cellNode = this.rowNode.children[ this.cellIndex ];
	this.cellNode = cellNode && cellNode.isCellable() ? cellNode : null;
	this.cellIndex++;
};
