/*!
 * VisualEditor ContentEditable linear escape key down handler
 *
 * @copyright See AUTHORS.txt
 */

/* istanbul ignore next */
/**
 * Escape key down handler for linear selections.
 *
 * @class
 * @extends ve.ce.KeyDownHandler
 *
 * @constructor
 */
ve.ce.LinearEscapeKeyDownHandler = function VeCeLinearEscapeKeyDownHandler() {
	// Parent constructor - never called because class is fully static
	// ve.ui.LinearEscapeKeyDownHandler.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.LinearEscapeKeyDownHandler, ve.ce.KeyDownHandler );

/* Static properties */

ve.ce.LinearEscapeKeyDownHandler.static.name = 'linearEscape';

ve.ce.LinearEscapeKeyDownHandler.static.keys = [ OO.ui.Keys.ESCAPE ];

ve.ce.LinearEscapeKeyDownHandler.static.supportedSelections = [ 'linear' ];

/* Static methods */

/**
 * @inheritdoc
 *
 * Handle escape key down events with a linear selection while table editing.
 */
ve.ce.LinearEscapeKeyDownHandler.static.execute = function ( surface, e ) {
	var activeTableNode = surface.getActiveNode() && surface.getActiveNode().findParent( ve.ce.TableNode );
	if ( activeTableNode ) {
		e.preventDefault();
		e.stopPropagation();
		activeTableNode.setEditing( false );
		// If this was a merged cell, we're going to have unexpected behavior when the selection moves,
		// so preemptively collapse to the top-left point of the merged cell.
		surface.getModel().setSelection( surface.getModel().getSelection().collapseToStart() );
		return true;
	}
	return false;
};

/* Registration */

ve.ce.keyDownHandlerFactory.register( ve.ce.LinearEscapeKeyDownHandler );
