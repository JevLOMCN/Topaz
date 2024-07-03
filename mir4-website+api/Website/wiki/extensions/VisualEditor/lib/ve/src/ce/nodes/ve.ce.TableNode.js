/*!
 * VisualEditor ContentEditable TableNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable table node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.TableNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.TableNode = function VeCeTableNode() {
	// Parent constructor
	ve.ce.TableNode.super.apply( this, arguments );

	// Properties
	this.surface = null;
	this.active = false;
	this.startCell = null;
	this.endCell = null;
	// Stores the original table selection as
	// a fragment when entering cell edit mode
	this.editingFragment = null;

	// DOM changes
	this.$element
		.addClass( 've-ce-tableNode' )
		.prop( 'contentEditable', 'false' );
};

/* Inheritance */

OO.inheritClass( ve.ce.TableNode, ve.ce.BranchNode );

/* Static properties */

ve.ce.TableNode.static.autoFocus = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ce.TableNode.prototype.onSetup = function () {
	// Parent method
	ve.ce.TableNode.super.prototype.onSetup.call( this );

	// Exit if already setup or not attached
	if ( this.surface || !this.root ) {
		return;
	}
	this.surface = this.getRoot().getSurface();

	// Overlay
	this.$selectionBox = $( '<div>' ).addClass( 've-ce-tableNodeOverlay-selection-box' );
	this.$selectionBoxAnchor = $( '<div>' ).addClass( 've-ce-tableNodeOverlay-selection-box-anchor' );
	if ( OO.ui.isMobile() ) {
		this.nodeContext = new ve.ui.TableLineContext( this, 'table' );
	} else {
		this.nodeContext = null;
	}
	this.colContext = new ve.ui.TableLineContext( this, 'col' );
	this.rowContext = new ve.ui.TableLineContext( this, 'row' );

	this.$overlay = $( '<div>' )
		.addClass( 've-ce-tableNodeOverlay oo-ui-element-hidden' )
		.append(
			this.$selectionBox,
			this.$selectionBoxAnchor,
			this.nodeContext ? this.nodeContext.$element : undefined,
			this.colContext.$element,
			this.rowContext.$element,
			this.$rowBracket,
			this.$colBracket
		);
	this.surface.surface.$blockers.append( this.$overlay );

	// Events
	this.$element.on( {
		'mousedown.ve-ce-tableNode': this.onTableMouseDown.bind( this ),
		'dblclick.ve-ce-tableNode': this.onTableDblClick.bind( this )
	} );
	this.$overlay.on( {
		'mousedown.ve-ce-tableNode': this.onTableMouseDown.bind( this ),
		'dblclick.ve-ce-tableNode': this.onTableDblClick.bind( this )
	} );
	this.onTableMouseUpHandler = this.onTableMouseUp.bind( this );
	this.onTableMouseMoveHandler = this.onTableMouseMove.bind( this );
	// Select and position events both fire updateOverlay, so debounce. Also makes
	// sure that this.selectedRectangle is up to date before redrawing.
	this.updateOverlayDebounced = ve.debounce( this.updateOverlay.bind( this ) );
	this.surface.getModel().connect( this, { select: 'onSurfaceModelSelect' } );
	this.surface.connect( this, {
		position: this.updateOverlayDebounced,
		activation: 'onSurfaceActivation'
	} );
};

/**
 * @inheritdoc
 */
ve.ce.TableNode.prototype.onTeardown = function () {
	// Parent method
	ve.ce.TableNode.super.prototype.onTeardown.call( this );

	// Not yet setup
	if ( !this.surface ) {
		return;
	}

	// Events
	this.$element.off( '.ve-ce-tableNode' );
	this.$overlay.off( '.ve-ce-tableNode' );
	this.surface.getModel().disconnect( this );
	this.surface.disconnect( this );
	this.$overlay.remove();

	this.surface = null;
};

/**
 * Handle table double click events
 *
 * @param {jQuery.Event} e Double click event
 */
ve.ce.TableNode.prototype.onTableDblClick = function ( e ) {
	if ( !this.getCellNodeFromEvent( e ) ) {
		return;
	}
	if ( this.surface.getModel().getSelection() instanceof ve.dm.TableSelection ) {
		// Don't change selection in setEditing to avoid scrolling to bottom of cell
		this.setEditing( true, true );
		// getOffsetFromEventCoords doesn't work in ce=false in Firefox, so ensure
		// this is called after setEditing( true ).
		var offset = this.surface.getOffsetFromEventCoords( e.originalEvent );
		if ( offset !== -1 ) {
			// Set selection to where the double click happened
			this.surface.getModel().setLinearSelection( new ve.Range( offset ) );
		} else {
			this.setEditing( true );
		}
	}
};

/**
 * Handle mouse down or touch start events
 *
 * @param {jQuery.Event} e Mouse down or touch start event
 */
ve.ce.TableNode.prototype.onTableMouseDown = function ( e ) {
	var node = this;

	var cellNode = this.getCellNodeFromEvent( e );
	if ( !cellNode ) {
		return;
	}

	var endCell = this.getModel().getMatrix().lookupCell( cellNode.getModel() );
	if ( !endCell ) {
		e.preventDefault();
		return;
	}
	var selection = this.surface.getModel().getSelection();

	var startCell;
	var newSelection;
	if ( e.shiftKey && this.active ) {
		// Extend selection from the anchor cell
		if ( selection instanceof ve.dm.TableSelection ) {
			startCell = { col: selection.fromCol, row: selection.fromRow };
		} else {
			startCell = this.getModel().getMatrix().lookupCell( this.getActiveCellNode().getModel() );
		}
	} else if (
		( e.which === OO.ui.MouseButtons.RIGHT || this.surface.isDeactivated() ) &&
		selection instanceof ve.dm.TableSelection &&
		selection.containsCell( endCell )
	) {
		// Right click within the current selection, or any click in deactviated selection:
		// leave selection as is
		newSelection = selection;
		// Make sure there's a startCell
		startCell = this.startCell || endCell;
	} else {
		// Select single cell
		startCell = endCell;
	}

	if ( !newSelection ) {
		newSelection = new ve.dm.TableSelection(
			this.getModel().getOuterRange(),
			startCell.col,
			startCell.row,
			endCell.col,
			endCell.row
		);
		newSelection = newSelection.expand( this.getModel().getDocument() );
	}

	if ( this.editingFragment ) {
		if ( newSelection.equals( this.editingFragment.getSelection() ) ) {
			// Clicking on the editing cell, don't prevent default
			return;
		} else {
			this.setEditing( false, true );
		}
	}
	this.surface.getModel().setSelection( newSelection );
	// Ensure surface is active as native 'focus' event won't be fired
	this.surface.activate();

	// Right-click on a cell which isn't being edited
	if ( e.which === OO.ui.MouseButtons.RIGHT && !this.getActiveCellNode() ) {
		// The same technique is used in ve.ce.FocusableNode
		// Make ce=true so we get cut/paste options in the context menu
		cellNode.$element.prop( 'contentEditable', true );
		// Select the clicked element so we get a copy option in the context menu
		ve.selectElement( cellNode.$element[ 0 ] );
		setTimeout( function () {
			// Undo ce=true as soon as the context menu is shown
			cellNode.$element.prop( 'contentEditable', 'false' );
			// Trigger onModelSelect to restore the selection
			node.surface.onModelSelect();
		} );
		return;
	}

	this.startCell = startCell;
	this.endCell = endCell;
	if ( !( selection instanceof ve.dm.TableSelection ) && OO.ui.isMobile() ) {
		// On mobile, fall through to the double-click behavior on a single tap --
		// this will place the cursor within the cell, rather than remaining in
		// table-selection mode.
		// As we just have only just set the table selection, the surface is in
		// process of deactivating, so wait for the event loop to clear before
		// continuing.
		setTimeout( function () {
			node.onTableDblClick( e );
		} );
	} else {
		this.surface.$document.on( {
			'mouseup touchend': this.onTableMouseUpHandler,
			'mousemove touchmove': this.onTableMouseMoveHandler
		} );
	}
	e.preventDefault();
};

/**
 * Get a table cell node from a mouse event
 *
 * Works around various issues with touch events and browser support.
 *
 * @param {jQuery.Event} e Mouse event
 * @return {ve.ce.TableCellNode|null} Table cell node
 */
ve.ce.TableNode.prototype.getCellNodeFromEvent = function ( e ) {
	// 'touchmove' doesn't give a correct e.target, so calculate it from coordinates
	if ( e.type === 'touchstart' && e.originalEvent.touches.length > 1 ) {
		// Ignore multi-touch
		return null;
	} else if ( e.type === 'touchmove' ) {
		if ( e.originalEvent.touches.length > 1 ) {
			// Ignore multi-touch
			return null;
		}
		var touch = e.originalEvent.touches[ 0 ];
		return this.getCellNodeFromPoint( touch.clientX, touch.clientY );
	} else {
		return this.getNearestCellNode( e.target );
	}
};

/**
 * Get the cell node from a point
 *
 * @param {number} x X offset
 * @param {number} y Y offset
 * @return {ve.ce.TableCellNode|null} Table cell node, or null if none found
 */
ve.ce.TableNode.prototype.getCellNodeFromPoint = function ( x, y ) {
	return this.getNearestCellNode(
		this.surface.getElementDocument().elementFromPoint( x, y )
	);
};

/**
 * Get the nearest cell node in this table to an element
 *
 * If the nearest cell node is in another table, return null.
 *
 * @param {HTMLElement} element Element target to find nearest cell node to
 * @return {ve.ce.TableCellNode|null} Table cell node, or null if none found
 */
ve.ce.TableNode.prototype.getNearestCellNode = function ( element ) {
	var $element = $( element ),
		$table = $element.closest( 'table' );

	// Nested table, ignore
	if ( !this.$element.is( $table ) ) {
		return null;
	}

	return $element.closest( 'td, th' ).data( 'view' );
};

/**
 * Handle mouse/touch move events
 *
 * @param {jQuery.Event} e Mouse/touch move event
 */
ve.ce.TableNode.prototype.onTableMouseMove = function ( e ) {
	var endCellNode = this.getCellNodeFromEvent( e );
	if ( !endCellNode ) {
		return;
	}

	var endCell = this.getModel().matrix.lookupCell( endCellNode.getModel() );
	if ( !endCell || endCell === this.endCell ) {
		return;
	}

	this.endCell = endCell;

	var selection = new ve.dm.TableSelection(
		this.getModel().getOuterRange(),
		this.startCell.col, this.startCell.row, endCell.col, endCell.row
	);
	selection = selection.expand( this.getModel().getDocument() );
	this.surface.getModel().setSelection( selection );
};

/**
 * Handle mouse up or touch end events
 *
 * @param {jQuery.Event} e Mouse up or touch end event
 */
ve.ce.TableNode.prototype.onTableMouseUp = function () {
	this.startCell = null;
	this.endCell = null;
	this.surface.$document.off( {
		'mouseup touchend': this.onTableMouseUpHandler,
		'mousemove touchmove': this.onTableMouseMoveHandler
	} );
};

/**
 * Set the editing state of the table
 *
 * @param {boolean} isEditing The table is being edited
 * @param {boolean} noSelect Don't change the selection
 */
ve.ce.TableNode.prototype.setEditing = function ( isEditing, noSelect ) {
	var surfaceModel = this.surface.getModel(),
		documentModel = surfaceModel.getDocument(),
		selection = surfaceModel.getSelection();

	if ( isEditing ) {
		if ( !selection.isSingleCell( documentModel ) ) {
			selection = selection.collapseToFrom();
			this.surface.getModel().setSelection( selection );
		}
		var cell = this.getCellNodesFromSelection( selection )[ 0 ];
		if ( !cell.isCellEditable() ) {
			return;
		}
		this.editingFragment = this.surface.getModel().getFragment( selection );
		cell.setEditing( true );
		if ( !noSelect ) {
			var cellRange = cell.getModel().getRange();
			var offset = surfaceModel.getDocument().data.getNearestContentOffset( cellRange.end, -1 );
			if ( offset > cellRange.start ) {
				surfaceModel.setLinearSelection( new ve.Range( offset ) );
			}
		}
	} else {
		var activeCellNode;
		if ( ( activeCellNode = this.getActiveCellNode() ) ) {
			activeCellNode.setEditing( false );
			if ( !noSelect ) {
				surfaceModel.setSelection( this.editingFragment.getSelection() );
			}
		}
		this.editingFragment = null;
	}

	this.$element.toggleClass( 've-ce-tableNode-editing', isEditing );
	this.$overlay.toggleClass( 've-ce-tableNodeOverlay-editing', isEditing );
};

/**
 * Handle select events from the surface model.
 *
 * @param {ve.dm.Selection} selection
 */
ve.ce.TableNode.prototype.onSurfaceModelSelect = function ( selection ) {
	// The table is active if there is a linear selection inside a cell being edited
	// or a table selection matching this table.
	var active =
		(
			this.editingFragment !== null &&
			selection instanceof ve.dm.LinearSelection &&
			this.editingFragment.getSelection().getRanges(
				this.editingFragment.getDocument()
			)[ 0 ].containsRange( selection.getRange() )
		) ||
		(
			selection instanceof ve.dm.TableSelection &&
			selection.tableRange.equalsSelection( this.getModel().getOuterRange() )
		);

	if ( active ) {
		if ( !this.active ) {
			this.$overlay.removeClass( 'oo-ui-element-hidden' );
			// Only register touchstart event after table has become active to prevent
			// accidental focusing of the table while scrolling
			this.$element.on( 'touchstart.ve-ce-tableNode', this.onTableMouseDown.bind( this ) );
		}
		// Ignore update the overlay if the table selection changed, i.e. not an in-cell selection change
		if ( selection instanceof ve.dm.TableSelection ) {
			if ( this.editingFragment ) {
				this.setEditing( false, true );
			}
			this.updateOverlayDebounced();
		}
	} else if ( !active && this.active ) {
		this.$overlay.addClass( 'oo-ui-element-hidden' );
		if ( this.editingFragment ) {
			this.setEditing( false, true );
		}
		// When the table of the active node is deactivated, clear the active node
		if ( this.getActiveCellNode() ) {
			this.surface.setActiveNode( null );
		}
		this.$element.off( 'touchstart.ve-ce-tableNode' );
	}
	this.$element.toggleClass( 've-ce-tableNode-active', active );
	this.active = active;
};

/**
 * Get the active node in this table, if it has one
 *
 * @return {ve.ce.TableNode|null} The active cell node in this table
 */
ve.ce.TableNode.prototype.getActiveCellNode = function () {
	var activeNode = this.surface.getActiveNode(),
		tableNodeOfActiveCellNode = activeNode && activeNode instanceof ve.ce.TableCellNode && activeNode.findParent( ve.ce.TableNode );

	return tableNodeOfActiveCellNode === this ? activeNode : null;
};

/**
 * Handle activation events from the surface
 */
ve.ce.TableNode.prototype.onSurfaceActivation = function () {
	this.$overlay.toggleClass( 've-ce-tableNodeOverlay-deactivated', !!this.surface.isShownAsDeactivated() );
};

/**
 * Update the overlay positions
 */
ve.ce.TableNode.prototype.updateOverlay = function () {
	if (
		!this.active || !this.root ||
		!this.surface ||
		// Overlay isn't attached, e.g. in tests
		!this.surface.surface.$blockers[ 0 ].parentNode
	) {
		return;
	}

	var selection = this.editingFragment ?
		this.editingFragment.getSelection() :
		this.surface.getModel().getSelection();
	var documentModel = this.editingFragment ?
		this.editingFragment.getDocument() :
		this.surface.getModel().getDocument();
	// getBoundingClientRect is more accurate but must be used consistently
	// due to the iOS7 bug where it is relative to the document.
	var tableOffset = this.getFirstSectionNode().$element[ 0 ].getBoundingClientRect();
	var surfaceOffset = this.surface.getSurface().$element[ 0 ].getBoundingClientRect();

	if ( !tableOffset ) {
		return;
	}

	var selectionRect = this.surface.getSelection( selection ).getSelectionBoundingRect();

	if ( !selectionRect ) {
		return;
	}

	// Compute a bounding box for the given cell elements
	var selectionOffset = ve.translateRect(
		selectionRect,
		surfaceOffset.left - tableOffset.left, surfaceOffset.top - tableOffset.top
	);

	var anchorOffset;
	if ( selection.isSingleCell( documentModel ) ) {
		// Optimization, use same rects as whole selection
		anchorOffset = selectionOffset;
	} else {
		anchorOffset = ve.translateRect(
			this.surface.getSelection( selection.collapseToFrom() ).getSelectionBoundingRect(),
			surfaceOffset.left - tableOffset.left, surfaceOffset.top - tableOffset.top
		);
	}

	// Resize controls
	this.$selectionBox.css( {
		top: selectionOffset.top,
		left: selectionOffset.left,
		width: selectionOffset.width,
		height: selectionOffset.height
	} );
	this.$selectionBoxAnchor.css( {
		top: anchorOffset.top,
		left: anchorOffset.left,
		width: anchorOffset.width,
		height: anchorOffset.height
	} );

	// Position controls
	this.$overlay.css( {
		top: tableOffset.top - surfaceOffset.top,
		left: tableOffset.left - surfaceOffset.left,
		width: tableOffset.width
	} );
	// this.nodeContext doesn't need to adjust to the line
	this.colContext.icon.$element.css( {
		left: selectionOffset.left,
		width: selectionOffset.width
	} );
	this.rowContext.icon.$element.css( {
		top: selectionOffset.top,
		height: selectionOffset.height
	} );

	if ( this.nodeContext ) {
		this.nodeContext.$element.toggleClass( 'oo-ui-element-hidden', this.surface.isReadOnly() );
	}
	this.colContext.$element.toggleClass( 'oo-ui-element-hidden', this.surface.isReadOnly() );
	this.rowContext.$element.toggleClass( 'oo-ui-element-hidden', this.surface.isReadOnly() );

	// Classes
	this.$selectionBox.toggleClass( 've-ce-tableNodeOverlay-selection-box-notEditable', !selection.isEditable( documentModel ) );
};

/**
 * Get the first section node of the table, skipping over any caption nodes
 *
 * @return {ve.ce.TableSectionNode} First table section node
 */
ve.ce.TableNode.prototype.getFirstSectionNode = function () {
	var i = 0;
	while ( !( this.children[ i ] instanceof ve.ce.TableSectionNode ) ) {
		i++;
	}
	return this.children[ i ];
};

/**
 * Get a cell node from a single cell selection
 *
 * @param {ve.dm.TableSelection} selection Single cell table selection
 * @return {ve.ce.TableCellNode[]} Cell nodes
 */
ve.ce.TableNode.prototype.getCellNodesFromSelection = function ( selection ) {
	var cells = selection.getMatrixCells( this.getModel().getDocument() ),
		nodes = [];

	for ( var i = 0, l = cells.length; i < l; i++ ) {
		var cellModel = cells[ i ].node;
		var cellView = this.getNodeFromOffset( cellModel.getOffset() - this.model.getOffset() );
		nodes.push( cellView );
	}
	return nodes;
};

/* Static Properties */

ve.ce.TableNode.static.name = 'table';

ve.ce.TableNode.static.tagName = 'table';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.TableNode );
