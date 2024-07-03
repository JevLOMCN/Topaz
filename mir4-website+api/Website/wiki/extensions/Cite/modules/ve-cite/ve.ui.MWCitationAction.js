'use strict';

/*!
 * VisualEditor UserInterface MWCitationAction class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * Citation action.
 *
 * Opens the {@see ve.ui.MWCitationDialog} (via it's name "cite") in VisualEditor.
 *
 * @constructor
 * @extends ve.ui.Action
 * @param {ve.ui.Surface} surface Surface to act on
 */
ve.ui.MWCitationAction = function VeUiMWCitationAction() {
	// Parent constructor
	ve.ui.MWCitationAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWCitationAction, ve.ui.Action );

/* Static Properties */

ve.ui.MWCitationAction.static.name = 'mwcite';

ve.ui.MWCitationAction.static.methods = [ 'open' ];

/* Methods */

/**
 * When opening a citation, send the dialog a property of the surface
 * dialog name.
 *
 * @param {Object} windowData Data to send to the dialog
 * @return {boolean} Action was executed
 */
ve.ui.MWCitationAction.prototype.open = function ( windowData ) {
	windowData = $.extend( {
		inDialog: this.surface.getInDialog()
	}, windowData );

	this.surface.execute( 'window', 'open', 'cite', windowData );
	return true;
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.MWCitationAction );
