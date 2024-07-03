/*!
 * VisualEditor ContentEditable table delete key down handler
 *
 * @copyright See AUTHORS.txt
 */

/* istanbul ignore next */
/**
 * Delete key down handler for table selections.
 *
 * @class
 * @extends ve.ce.KeyDownHandler
 *
 * @constructor
 */
ve.ce.TableDeleteKeyDownHandler = function VeCeTableDeleteKeyDownHandler() {
	// Parent constructor - never called because class is fully static
	// ve.ui.TableDeleteKeyDownHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.TableDeleteKeyDownHandler, ve.ce.KeyDownHandler );

/* Static properties */

ve.ce.TableDeleteKeyDownHandler.static.name = 'tableDelete';

ve.ce.TableDeleteKeyDownHandler.static.keys = [ OO.ui.Keys.BACKSPACE, OO.ui.Keys.DELETE ];

ve.ce.TableDeleteKeyDownHandler.static.supportedSelections = [ 'table' ];

/* Static methods */

/**
 * @inheritdoc
 *
 * Handle delete and backspace key down events with a table selection.
 *
 * Performs a strip-delete removing all the cell contents but not altering the structure.
 */
ve.ce.TableDeleteKeyDownHandler.static.execute = function ( surface, e ) {
	var surfaceModel = surface.getModel(),
		documentModel = surfaceModel.getDocument(),
		fragments = [],
		cells = surfaceModel.getSelection().getMatrixCells( documentModel );

	if ( e ) {
		e.preventDefault();
	}

	if ( surface.isReadOnly() ) {
		return true;
	}

	var i, l;
	for ( i = 0, l = cells.length; i < l; i++ ) {
		if ( cells[ i ].node.isCellEditable() ) {
			// Create auto-updating fragments from ranges
			fragments.push( surfaceModel.getLinearFragment( cells[ i ].node.getRange(), true ) );
		}
	}

	for ( i = 0, l = fragments.length; i < l; i++ ) {
		// Replace contents with empty wrapper paragraphs
		fragments[ i ].insertContent( [
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{ type: '/paragraph' }
		] );
	}

	return true;
};

/* Registration */

ve.ce.keyDownHandlerFactory.register( ve.ce.TableDeleteKeyDownHandler );
