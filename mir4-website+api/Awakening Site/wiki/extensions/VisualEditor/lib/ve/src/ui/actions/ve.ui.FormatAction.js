/*!
 * VisualEditor UserInterface FormatAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Format action.
 *
 * @class
 * @extends ve.ui.Action
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 * @param {string} [source]
 */
ve.ui.FormatAction = function VeUiFormatAction() {
	// Parent constructor
	ve.ui.FormatAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.FormatAction, ve.ui.Action );

/* Static Properties */

ve.ui.FormatAction.static.name = 'format';

ve.ui.FormatAction.static.methods = [ 'convert' ];

/* Methods */

/**
 * Convert the format of content.
 *
 * Conversion splits and unwraps all lists and replaces content branch nodes.
 *
 * TODO: Refactor functionality into {ve.dm.SurfaceFragment}.
 *
 * @param {string} type
 * @param {Object} attributes
 * @return {boolean} Action was executed
 */
ve.ui.FormatAction.prototype.convert = function ( type, attributes ) {
	var surfaceModel = this.surface.getModel(),
		fragment = surfaceModel.getFragment(),
		fragmentSelection = fragment.getSelection();

	if ( !( fragmentSelection instanceof ve.dm.LinearSelection ) ) {
		return;
	}

	var fragments = [];

	// We can't have headings or pre's in a list, so if we're trying to convert
	// things that are in lists to a heading or a pre, split the list
	fragment.getSelectedLeafNodes().forEach( function ( node ) {
		var contentBranch = node.isContent() ? node.getParent() : node;

		fragments.push( surfaceModel.getLinearFragment( contentBranch.getOuterRange(), true ) );
	} );

	fragments.forEach( function ( f ) {
		f.isolateAndUnwrap( type );
	} );

	fragment.convertNodes( type, attributes );
	if ( fragmentSelection.isCollapsed() ) {
		// Converting an empty node needs a small selection fixup afterwards,
		// otherwise the selection will be displayed outside the new empty
		// node. This causes issues with the display of the current format in
		// the toolbar, and with hitting enter if no content is entered. Don't
		// always reapply the selection, because the automatic behavior is
		// better if isolateAndUnwrap has actually acted. (T151594)
		surfaceModel.setSelection( fragmentSelection );
	}
	this.surface.getView().focus();

	ve.track( 'activity.format', { action: type + ( attributes && attributes.level ? ( '-' + attributes.level ) : '' ) } );

	return true;
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.FormatAction );
