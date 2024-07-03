/**
 * A helper class that allows random access to the table cells
 * and introduces place-holders for fields occupied by spanning cells,
 * making it a non-sparse representation of the sparse HTML model.
 * This is essential for the implementation of table manipulations, such as row insertions or deletions.
 *
 * Example:
 *
 *     <table>
 *       <tr><td rowspan=2>1</td><td colspan=2>2</td><td rowspan=2 colspan=2>3</td></tr>
 *       <tr><td>4</td><td>5</td></tr>
 *     </table>
 *
 * Visually this table would look like:
 *
 *      -------------------
 *     | 1 | 2     | 3     |
 *     |   |-------|       |
 *     |   | 4 | 5 |       |
 *      -------------------
 *
 * The HTML model is sparse which makes it hard to read but also difficult to work with programmatically.
 * The corresponding TableCellMatrix would look like:
 *
 *     | C[1] | C[2] | P[2] | C[3] | P[3] |
 *     | P[1] | C[4] | C[5] | P[3] | P[3] |
 *
 * Where C[1] represents a Cell instance wrapping cell 1,
 * and P[1] a PlaceHolder instance owned by that cell.
 *
 * @class
 * @mixins OO.EventEmitter
 * @constructor
 * @param {ve.dm.TableNode} tableNode Reference to a table instance
 */
ve.dm.TableMatrix = function VeDmTableMatrix( tableNode ) {
	// Mixin constructors
	OO.EventEmitter.call( this );

	this.tableNode = tableNode;
	// Do not access these directly as they get invalidated on structural changes
	// Use the accessor methods instead.
	this.matrix = null;
	this.rowNodes = null;
};

/* Inheritance */

OO.mixinClass( ve.dm.TableMatrix, OO.EventEmitter );

/**
 * @event structureChange
 */

/**
 * Invalidates the matrix structure.
 *
 * This is called by ve.dm.TableNode on structural changes.
 *
 * @fires structureChange
 */
ve.dm.TableMatrix.prototype.invalidate = function () {
	this.matrix = null;
	this.rowNodes = null;
	this.emit( 'structureChange' );
};

/**
 * Recreates the matrix structure.
 */
ve.dm.TableMatrix.prototype.update = function () {
	var matrix = [],
		rowNodes = [],
		iterator = this.tableNode.getIterator(),
		row = -1,
		col = -1;

	// Handle row transitions
	iterator.on( 'newRow', function ( rowNode ) {
		row++;
		col = -1;
		// Initialize a matrix row
		matrix[ row ] = matrix[ row ] || [];
		// Store the row node
		rowNodes.push( rowNode );
	} );

	// Iterates through all cells and stores the cells as well as
	// so called placeholders into the matrix.
	var cellNode;
	while ( ( cellNode = iterator.next() ) !== undefined ) {
		col++;
		// Skip placeholders
		while ( matrix[ row ][ col ] ) {
			col++;
		}
		if ( !cellNode ) {
			matrix[ row ][ col ] = null;
			continue;
		}
		var cell = new ve.dm.TableMatrixCell( cellNode, row, col );
		// Store the cell in the matrix
		matrix[ row ][ col ] = cell;
		// Add place holders for spanned cells
		var rowSpan = cellNode.getRowspan();
		var colSpan = cellNode.getColspan();

		if ( rowSpan === 1 && colSpan === 1 ) {
			continue;
		}

		for ( var i = 0; i < rowSpan; i++ ) {
			for ( var j = 0; j < colSpan; j++ ) {
				if ( i === 0 && j === 0 ) {
					continue;
				}
				var r = row + i;
				var c = col + j;
				// Initialize the cell matrix row if not yet present
				matrix[ r ] = matrix[ r ] || [];
				matrix[ r ][ c ] = new ve.dm.TableMatrixCell( cellNode, r, c, cell );
			}
		}
	}
	this.matrix = matrix;
	this.rowNodes = rowNodes;
};

/**
 * Retrieves a single cell.
 *
 * @param {number} row Row index
 * @param {number} col Column index
 * @return {ve.dm.TableMatrixCell|undefined} Cell, or undefined if out of bounds
 */
ve.dm.TableMatrix.prototype.getCell = function ( row, col ) {
	var matrix = this.getMatrix();
	return matrix[ row ] ? matrix[ row ][ col ] : undefined;
};

/**
 * Retrieves all cells of a column with given index.
 *
 * @param {number} col Column index
 * @return {ve.dm.TableMatrixCell[]} The cells of a column
 */
ve.dm.TableMatrix.prototype.getColumn = function ( col ) {
	var matrix = this.getMatrix();
	var cells = [];
	for ( var row = 0; row < matrix.length; row++ ) {
		cells.push( matrix[ row ][ col ] );
	}
	return cells;
};

/**
 * Retrieves all cells of a row with given index.
 *
 * @param {number} row Row index
 * @return {ve.dm.TableMatrixCell[]} The cells of a row
 */
ve.dm.TableMatrix.prototype.getRow = function ( row ) {
	var matrix = this.getMatrix();
	return matrix[ row ];
};

/**
 * Retrieves the row node of a row with given index.
 *
 * It is possible for bad tables to be constructed with rowspans
 * that exceed the last actual row node. In these cases, the number
 * of rows in the table matrix will exceed the number of row
 * nodes, so users should expected getRowNode to sometimes
 * return undefined. (T191858)
 *
 * @param {number} row Row index
 * @return {ve.dm.TableRowNode|undefined} Node at given index, if found
 */
ve.dm.TableMatrix.prototype.getRowNode = function ( row ) {
	var rowNodes = this.getRowNodes();
	return rowNodes[ row ];
};

/**
 * Provides a reference to the internal cell matrix.
 *
 * Note: this is primarily for internal use. Do not change the delivered matrix
 * and do not store as it may be invalidated.
 *
 * @return {ve.dm.TableMatrixCell[][]} Table matrix
 */
ve.dm.TableMatrix.prototype.getMatrix = function () {
	if ( !this.matrix ) {
		this.update();
	}
	return this.matrix;
};

/**
 * Provides a reference to the internal array of row nodes.
 *
 * Note: this is primarily for internal use. Do not change the delivered array
 * and do not store it as it may be invalidated.
 *
 * @return {ve.dm.TableRowNode[]} Table row nodes
 */
ve.dm.TableMatrix.prototype.getRowNodes = function () {
	if ( !this.rowNodes ) {
		this.update();
	}
	return this.rowNodes;
};

/**
 * Get number of rows in the table
 *
 * @return {number} Number of rows
 */
ve.dm.TableMatrix.prototype.getRowCount = function () {
	return this.getMatrix().length;
};

/**
 * Get number of columns in a row
 *
 * To get the number of columns in a table use #getMaxColCount
 *
 * @param {number} row Row to count columns in
 * @return {number} Number of columns
 */
ve.dm.TableMatrix.prototype.getColCount = function ( row ) {
	var matrix = this.getMatrix();
	return matrix.length ? matrix[ row ].length : 0;
};

/**
 * Get the maximum number of columns in a table
 *
 * This is required because in sparse tables the column count is variable.
 *
 * @return {number} Number of columns
 */
ve.dm.TableMatrix.prototype.getMaxColCount = function () {
	var colCount = 0;

	for ( var row = this.getRowCount() - 1; row >= 0; row-- ) {
		colCount = Math.max( colCount, this.getColCount( row ) );
	}
	return colCount;
};

/**
 * Look up the matrix cell for a given cell node.
 *
 * @param {ve.dm.TableCellNode} cellNode Cell node
 * @return {ve.dm.TableMatrixCell|null} The cell or null if not found
 */
ve.dm.TableMatrix.prototype.lookupCell = function ( cellNode ) {
	var matrix = this.getMatrix(),
		rowNodes = this.getRowNodes();

	var row = rowNodes.indexOf( cellNode.getParent() );
	if ( row < 0 ) {
		return null;
	}
	var rowCells = matrix[ row ];
	for ( var col = 0, cols = rowCells.length; col < cols; col++ ) {
		if ( rowCells[ col ] && rowCells[ col ].node === cellNode ) {
			return rowCells[ col ];
		}
	}
	return null;
};

/**
 * Finds the closest cell not being a placeholder for a given cell.
 *
 * @param {ve.dm.TableMatrixCell} cell Table cell
 * @return {ve.dm.TableMatrixCell|null} Closest cell
 */
ve.dm.TableMatrix.prototype.findClosestCell = function ( cell ) {
	var matrix = this.getMatrix();

	var rowCells = matrix[ cell.row ];
	var col;
	for ( col = cell.col; col >= 0; col-- ) {
		if ( !rowCells[ col ].isPlaceholder() ) {
			return rowCells[ col ];
		}
	}
	var cols;
	for ( col = cell.col + 1, cols = rowCells.length; col < cols; col++ ) {
		if ( !rowCells[ col ].isPlaceholder() ) {
			return rowCells[ col ];
		}
	}
	return null;
};

/**
 * An object wrapping a table cell node, augmenting it with row and column indexes.
 *
 * Cells which are occupied by another cell's with 'rowspan' or 'colspan' attributes are
 * placeholders and have an owner property other than themselves.
 * Placeholders are used to create a dense representation of the sparse HTML table model.
 *
 * @class
 * @constructor
 * @param {ve.dm.TableCellNode} node DM Node
 * @param {number} row Row index
 * @param {number} col Column index
 * @param {ve.dm.TableMatrixCell} [owner] Owner cell if this is a placeholder
 */
ve.dm.TableMatrixCell = function VeDmTableMatrixCell( node, row, col, owner ) {
	this.node = node;
	this.row = row;
	this.col = col;
	this.key = row + '_' + col;
	this.owner = owner || this;
	// Used when moving cells
	this.data = null;
};

/* Inheritance */

OO.initClass( ve.dm.TableMatrixCell );

/* Static Methods */

/**
 * Comparison function for sorting cells in text flow order
 *
 * @param {ve.dm.TableMatrixCell} a First cell
 * @param {ve.dm.TableMatrixCell} b Second cell
 * @return {number} Positive, negative or zero, depending on relative position
 */
ve.dm.TableMatrixCell.static.sortDescending = function ( a, b ) {
	if ( a.row !== b.row ) {
		return b.row - a.row;
	}
	return b.col - a.col;
};

/* Methods */

/**
 * Check if this cell is a placeholder
 *
 * @return {boolean} This cell is a placeholder
 */
ve.dm.TableMatrixCell.prototype.isPlaceholder = function () {
	return this.owner !== this;
};

/**
 * Get owner matrix cell
 *
 * @return {ve.dm.TableMatrixCell} Owner cell
 */
ve.dm.TableMatrixCell.prototype.getOwner = function () {
	return this.owner;
};

/**
 * Compare to another cell
 *
 * Cells are considered equal to their placeholders
 *
 * @param {ve.dm.TableMatrixCell} other Cell to compare
 * @return {boolean} Cells are equal
 */
ve.dm.TableMatrixCell.prototype.equals = function ( other ) {
	return this.getOwner().key === other.getOwner().key;
};
