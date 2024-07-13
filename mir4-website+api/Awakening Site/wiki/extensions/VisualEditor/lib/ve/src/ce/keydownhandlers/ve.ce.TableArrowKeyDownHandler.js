/*!
 * VisualEditor ContentEditable table arrow key down handler
 *
 * @copyright See AUTHORS.txt
 */

/* istanbul ignore next */
/**
 * Arrow key down handler for table selections.
 *
 * @class
 * @extends ve.ce.KeyDownHandler
 *
 * @constructor
 */
ve.ce.TableArrowKeyDownHandler = function VeCeTableArrowKeyDownHandler() {
	// Parent constructor - never called because class is fully static
	// ve.ui.TableArrowKeyDownHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.TableArrowKeyDownHandler, ve.ce.KeyDownHandler );

/* Static properties */

ve.ce.TableArrowKeyDownHandler.static.name = 'tableArrow';

ve.ce.TableArrowKeyDownHandler.static.keys = [
	OO.ui.Keys.UP, OO.ui.Keys.DOWN, OO.ui.Keys.LEFT, OO.ui.Keys.RIGHT,
	OO.ui.Keys.HOME, OO.ui.Keys.END, OO.ui.Keys.PAGEUP, OO.ui.Keys.PAGEDOWN,
	OO.ui.Keys.TAB
];

ve.ce.TableArrowKeyDownHandler.static.supportedSelections = [ 'table' ];

/* Static methods */

/**
 * @inheritdoc
 */
ve.ce.TableArrowKeyDownHandler.static.execute = function ( surface, e ) {
	var wrap = false,
		checkDir = false,
		colOffset = 0,
		rowOffset = 0,
		expand = e.shiftKey;

	if ( e.ctrlKey || e.altKey || e.metaKey ) {
		// Support: Firefox
		// In Firefox, ctrl-tab to switch browser-tabs still triggers the
		// keydown event.
		return;
	}

	switch ( e.keyCode ) {
		case OO.ui.Keys.LEFT:
			colOffset = -1;
			checkDir = true;
			break;
		case OO.ui.Keys.RIGHT:
			colOffset = 1;
			checkDir = true;
			break;
		case OO.ui.Keys.UP:
			rowOffset = -1;
			break;
		case OO.ui.Keys.DOWN:
			rowOffset = 1;
			break;
		case OO.ui.Keys.HOME:
			colOffset = -Infinity;
			break;
		case OO.ui.Keys.END:
			colOffset = Infinity;
			break;
		case OO.ui.Keys.PAGEUP:
			rowOffset = -Infinity;
			break;
		case OO.ui.Keys.PAGEDOWN:
			rowOffset = Infinity;
			break;
		case OO.ui.Keys.TAB:
			colOffset = e.shiftKey ? -1 : 1;
			expand = false; // Shift-tab is a movement, not an expansion
			wrap = true;
			break;
	}

	e.preventDefault();

	this.moveTableSelection( surface, rowOffset, colOffset, checkDir, expand, wrap );
	return true;
};

/**
 * @param {ve.ce.Surface} surface
 * @param {number} rowOffset how many rows to move
 * @param {number} colOffset how many columns to move
 * @param {boolean} checkDir whether to translate offsets according to ltr settings
 * @param {boolean} expand whether to expand the selection or replace it
 * @param {boolean} wrap Wrap to the next/previous row at edges, insert new row at end
 */
ve.ce.TableArrowKeyDownHandler.static.moveTableSelection = function ( surface, rowOffset, colOffset, checkDir, expand, wrap ) {
	var selection = surface.getModel().getSelection();
	if ( colOffset && checkDir ) {
		var tableNode = surface.documentView.getBranchNodeFromOffset( selection.tableRange.start + 1 );
		if ( tableNode.$element.css( 'direction' ) !== 'ltr' ) {
			colOffset *= -1;
		}
	}
	if ( !expand ) {
		selection = selection.collapseToFrom();
	}

	var newSelection;

	function adjust() {
		newSelection = selection.newFromAdjustment(
			surface.getModel().getDocument(),
			expand ? 0 : colOffset,
			expand ? 0 : rowOffset,
			colOffset,
			rowOffset,
			wrap
		);
	}

	adjust();

	// If wrapping forwards didn't move, we must be at the end of the table,
	// so insert a new row and try again
	if ( wrap && colOffset > 0 && selection.equals( newSelection ) ) {
		surface.getSurface().execute( 'table', 'insert', 'row', 'after' );
		selection = surface.getModel().getSelection();
		adjust();
	}

	// If moving up/down didn't move, we must be at the start/end of the table,
	// so move outside
	if ( ( rowOffset !== 0 || ( rowOffset === 0 && colOffset === -1 && wrap ) ) && selection.equals( newSelection ) ) {
		var documentModel = surface.getModel().getDocument();
		var captionNode;
		if ( ( rowOffset === -1 || ( colOffset === -1 && wrap ) ) && ( captionNode = selection.getTableNode( documentModel ).getCaptionNode() ) ) {
			// If we're moving up/backwards, and there's a caption node, put the selection in it
			newSelection = new ve.dm.LinearSelection( documentModel.getRelativeRange( new ve.Range( captionNode.getRange().start ), 1 ) );
		} else {
			// Otherwise, go outside the table
			newSelection = new ve.dm.LinearSelection( documentModel.getRelativeRange( selection.tableRange, rowOffset || colOffset ) );
		}
	}

	surface.getModel().setSelection( newSelection );
};

/* Registration */

ve.ce.keyDownHandlerFactory.register( ve.ce.TableArrowKeyDownHandler );
