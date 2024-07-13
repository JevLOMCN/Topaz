/*!
 * VisualEditor DataModel TableRowNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel table row node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.TableRowNode = function VeDmTableRowNode() {
	// Parent constructor
	ve.dm.TableRowNode.super.apply( this, arguments );

	// Events
	this.connect( this, { splice: 'onSplice' } );
};

/* Inheritance */

OO.inheritClass( ve.dm.TableRowNode, ve.dm.BranchNode );

/**
 * @event cellAttributeChange
 * @param {ve.dm.TableCellableNode} cell
 */

/* Static Properties */

ve.dm.TableRowNode.static.name = 'tableRow';

ve.dm.TableRowNode.static.childNodeTypes = [ 'tableCell', 'alienTableCell' ];

ve.dm.TableRowNode.static.parentNodeTypes = [ 'tableSection' ];

ve.dm.TableRowNode.static.matchTagNames = [ 'tr' ];

/* Static Methods */

/**
 * Creates data that can be inserted into the model to create a new table row.
 *
 * @param {Object} [options] Creation options
 * @param {string|string[]} [options.style='data'] Cell style; 'data' or 'header', or array of styles
 * @param {number} [options.cellCount=1] Number of cells to create
 * @return {Array} Model data for a new table row
 */
ve.dm.TableRowNode.static.createData = function ( options ) {
	options = options || {};

	var cellCount = options.cellCount || 1;

	var data = [];
	data.push( { type: 'tableRow' } );
	for ( var i = 0; i < cellCount; i++ ) {
		data = data.concat( ve.dm.TableCellNode.static.createData( {
			style: Array.isArray( options.style ) ? options.style[ i ] : options.style
		} ) );
	}
	data.push( { type: '/tableRow' } );
	return data;
};

/* Methods */

/**
 * Handle splicing of child nodes
 */
ve.dm.TableRowNode.prototype.onSplice = function () {
	if ( this.getRoot() ) {
		this.getParent().getParent().getMatrix().invalidate();
	}
	var nodes = Array.prototype.slice.call( arguments, 2 );
	for ( var i = 0; i < nodes.length; i++ ) {
		nodes[ i ].connect( this, {
			attributeChange: [ 'onCellAttributeChange', nodes[ i ] ]
		} );
	}
};

/**
 * Handle cell attribute changes
 *
 * @param {ve.dm.TableCellableNode} cell
 * @fires cellAttributeChange
 */
ve.dm.TableRowNode.prototype.onCellAttributeChange = function ( cell ) {
	this.emit( 'cellAttributeChange', cell );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.TableRowNode );
