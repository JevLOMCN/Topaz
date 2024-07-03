/*!
 * VisualEditor ContentEditable TableRowNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable table row node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.TableRowNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.TableRowNode = function VeCeTableRowNode() {
	// Parent constructor
	ve.ce.TableRowNode.super.apply( this, arguments );

	this.$missingCell = null;
};

/* Inheritance */

OO.inheritClass( ve.ce.TableRowNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.TableRowNode.static.name = 'tableRow';

ve.ce.TableRowNode.static.tagName = 'tr';

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.TableRowNode.prototype.onSetup = function () {
	// Parent method
	ve.ce.TableRowNode.super.prototype.onSetup.apply( this, arguments );

	this.setupMissingCell();
};

/**
 * @inheritdoc
 */
ve.ce.TableRowNode.prototype.onSplice = function () {
	var node = this;
	// Parent method
	ve.ce.TableRowNode.super.prototype.onSplice.apply( this, arguments );

	// Defer call until after other changes in this cycle have been made
	setTimeout( function () {
		if ( node.getRoot() ) {
			// It's possible for this to have been removed from the model in the last tick
			// This mostly seems to happen during cell merges
			node.setupMissingCell();
		}
	} );
};

/**
 * Setup a slug for a missing cell, if this row contains fewer cells than the table
 */
ve.ce.TableRowNode.prototype.setupMissingCell = function () {
	var matrix = this.findParent( ve.ce.TableNode ).getModel().getMatrix(),
		maxColCount = matrix.getMaxColCount();

	var row = matrix.getRowNodes().indexOf( this.model );
	if ( maxColCount > matrix.getColCount( row ) ) {
		if ( !this.$missingCell ) {
			this.$missingCell = $( '<td>' )
				.prop( 'contentEditable', 'false' )
				.addClass( 've-ce-branchNode-slug ve-ce-branchNode-blockSlug ve-ce-tableNode-missingCell' );
			var slugButton = new ve.ui.NoFocusButtonWidget( {
				icon: 'add',
				framed: false
			} ).on( 'click', this.onMissingCellClick.bind( this ) );
			this.$missingCell.append( slugButton.$element );
		}
		this.$element.append( this.$missingCell );
	} else {
		this.removeSlugs();
	}
};

/**
 * @inheritdoc
 */
ve.ce.TableRowNode.prototype.removeSlugs = function () {
	if ( this.$missingCell ) {
		this.$missingCell.detach();
	}
};

/**
 * Handle click events on the missing cell slug
 *
 * @param {jQuery.Event} e Click event
 */
ve.ce.TableRowNode.prototype.onMissingCellClick = function () {
	var surfaceModel = this.getRoot().getSurface().getModel(),
		documentModel = surfaceModel.getDocument(),
		tableModel = this.findParent( ve.ce.TableNode ).getModel(),
		matrix = tableModel.getMatrix();

	// Add a cell onto the end of the row
	surfaceModel.change(
		ve.dm.TransactionBuilder.static.newFromInsertion(
			documentModel, this.getModel().getRange().end,
			ve.dm.TableCellNode.static.createData()
		)
	);

	// Select the newly-inserted cell
	var row = matrix.getRowNodes().indexOf( this.model );
	var col = matrix.getColCount( row ) - 1;
	surfaceModel.setSelection(
		new ve.dm.TableSelection( tableModel.getOuterRange(), col, row )
	);
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.TableRowNode );
