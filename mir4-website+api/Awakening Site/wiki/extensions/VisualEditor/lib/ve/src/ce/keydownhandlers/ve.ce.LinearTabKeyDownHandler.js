/*!
 * VisualEditor ContentEditable linear escape key down handler
 *
 * @copyright See AUTHORS.txt
 */

/* istanbul ignore next */
/**
 * Tab key down handler for linear selections.
 *
 * @class
 * @extends ve.ce.KeyDownHandler
 *
 * @constructor
 */
ve.ce.LinearTabKeyDownHandler = function VeCeLinearTabKeyDownHandler() {
	// Parent constructor - never called because class is fully static
	// ve.ui.LinearTabKeyDownHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.LinearTabKeyDownHandler, ve.ce.KeyDownHandler );

/* Static properties */

ve.ce.LinearTabKeyDownHandler.static.name = 'linearTab';

ve.ce.LinearTabKeyDownHandler.static.keys = [ OO.ui.Keys.TAB ];

ve.ce.LinearTabKeyDownHandler.static.supportedSelections = [ 'linear' ];

/* Static methods */

/**
 * @inheritdoc
 *
 * Handle tab key down events with a linear selection while table editing.
 */
ve.ce.LinearTabKeyDownHandler.static.execute = function ( surface, e ) {
	var activeTableNode = surface.getActiveNode() && surface.getActiveNode().findParent( ve.ce.TableNode ),
		activeTableCaptionNode = surface.getActiveNode() && surface.getActiveNode().findParent( ve.ce.TableCaptionNode ),
		documentModel = surface.getModel().getDocument();
	// Check we have an active table node
	if ( activeTableNode ) {
		if ( e.ctrlKey || e.altKey || e.metaKey ) {
			// Support: Firefox
			// In Firefox, ctrl-tab to switch browser-tabs still triggers the
			// keydown event.
			return;
		}

		if ( activeTableNode.editingFragment ) {
			// we are inside a cell (editingFragment), and not just a caption
			e.preventDefault();
			e.stopPropagation();
			activeTableNode.setEditing( false );
			// If this was a merged cell, we're going to have unexpected behavior when the selection moves,
			// so preemptively collapse to the top-left point of the merged cell.
			surface.getModel().setSelection( surface.getModel().getSelection().collapseToStart() );
			ve.ce.TableArrowKeyDownHandler.static.moveTableSelection(
				surface,
				0, // Rows
				e.shiftKey ? -1 : 1, // Columns
				false, // Logical direction, not visual
				false, // Don't expand the current selection,
				true // Wrap to next/previous row
			);
			if ( surface.getModel().getSelection() instanceof ve.dm.TableSelection ) {
				// Might have moved off the table and into the caption
				activeTableNode.setEditing( true );
			}
			return true;
		} else if ( activeTableCaptionNode ) {
			// We're in a table caption
			e.preventDefault();
			e.stopPropagation();
			if ( e.shiftKey ) {
				// back out of the table
				surface.getModel().setSelection( new ve.dm.LinearSelection( documentModel.getRelativeRange( activeTableNode.getRange(), -1 ) ) );
			} else {
				// move to the first cell
				surface.getModel().setSelection( new ve.dm.TableSelection( activeTableNode.getOuterRange(), 0, 0 ) );
			}
			return true;
		}
	}
	return false;
};

/* Registration */

ve.ce.keyDownHandlerFactory.register( ve.ce.LinearTabKeyDownHandler );
