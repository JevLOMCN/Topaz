/*!
 * VisualEditor ContentEditable MWTableNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MW table node.
 *
 * @class
 * @extends ve.ce.TableNode
 * @mixins ve.ce.ClassAttributeNode
 *
 * @constructor
 * @param {ve.dm.MWTableNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWTableNode = function VeCeMWTableNode() {
	// Parent constructor
	ve.ce.MWTableNode.super.apply( this, arguments );

	// Mixin constructors
	ve.ce.ClassAttributeNode.call( this );

	// Properties
	this.updateSortableHeadersHandler = ve.debounce( this.updateSortableHeaders );
	this.$sortableHeaders = $( [] );

	// Events
	this.connect( this, { setup: 'updateSortableHeadersHandler' } );
	this.model.connect( this, {
		// Update when the table is made sortable or not sortable
		attributeChange: 'updateSortableHeadersHandler',
		// Update when a cell style changes between content cell and header cell
		cellAttributeChange: 'updateSortableHeadersHandler'
	} );
	this.model.getMatrix().connect( this, {
		// Update when cells are added, removed, merged, split
		structureChange: 'updateSortableHeadersHandler'
	} );

	// DOM changes
	this.$element.addClass( 've-ce-mwTableNode' );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWTableNode, ve.ce.TableNode );

OO.mixinClass( ve.ce.MWTableNode, ve.ce.ClassAttributeNode );

/* Static Properties */

ve.ce.MWTableNode.static.name = 'mwTable';

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.MWTableNode.prototype.destroy = function () {
	this.model.getMatrix().disconnect( this );

	// Parent method
	ve.ce.MWTableNode.super.prototype.destroy.apply( this, arguments );
};

/**
 * Update sortable headers (if the table is sortable).
 *
 * @private
 */
ve.ce.MWTableNode.prototype.updateSortableHeaders = function () {
	var
		view = this,
		cellModels, cellViews;

	if ( !this.model ) {
		// Fired after teardown due to debounce
		return;
	}

	if ( this.model.getAttribute( 'collapsible' ) ) {
		mw.loader.load( 'jquery.makeCollapsible.styles' );
	}

	this.$element.toggleClass( 'jquery-tablesorter', this.model.getAttribute( 'sortable' ) );

	this.$sortableHeaders.removeClass( 'headerSort' );

	if ( this.model.getAttribute( 'sortable' ) ) {
		// We only really want the styles. But it's harmless to load the entire module, and if the user
		// ends up saving this change, it will be loaded anyway to render the real sortable table.
		mw.loader.load( 'jquery.tablesorter' );

		cellModels = this.getTablesorterHeaderCells();
		cellViews = cellModels.map( function ( cellModel ) {
			return view.getNodeFromOffset( cellModel.getOffset() - view.model.getOffset() );
		} );

		this.$sortableHeaders = $( cellViews.map( function ( cell ) {
			return cell.$element[ 0 ];
		} ) ).not( '.unsortable' );
	} else {
		this.$sortableHeaders = $( [] );
	}

	this.$sortableHeaders.addClass( 'headerSort' );

	// .headerSort class adds some padding, causing the overlays to become misaligned
	this.updateOverlay();
};

/**
 * Find the last of header rows with maximum number of cells (minimum number of colspans) and return
 * all of its cells. These are the cells that serve as sortable headers in jQuery Tablesorter.
 * This algorithm is exactly the same, see the buildHeaders() function in jquery.tablesorter.js.
 *
 * @private
 * @return {ve.dm.TableCellNode[]}
 */
ve.ce.MWTableNode.prototype.getTablesorterHeaderCells = function () {
	var
		matrix = this.model.getMatrix(),
		longestRow = [],
		longestRowLength = 0,
		i, l, matrixCells, isAllHeaders, rowLength, cellModels;

	for ( i = 0, l = matrix.getRowCount(); i < l; i++ ) {
		matrixCells = matrix.getRow( i );
		cellModels = OO.unique( matrixCells.map( function ( matrixCell ) {
			return matrixCell && matrixCell.getOwner().node;
		} ) );
		isAllHeaders = cellModels.every( function ( cellModel ) {
			return cellModel && cellModel.getAttribute( 'style' ) === 'header';
		} );
		if ( !isAllHeaders ) {
			// This is the end of table head (thead), stop looking further
			break;
		}
		rowLength = cellModels.length;
		if ( rowLength >= longestRowLength ) {
			longestRowLength = rowLength;
			longestRow = cellModels;
		}
	}

	return longestRow;
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWTableNode );
