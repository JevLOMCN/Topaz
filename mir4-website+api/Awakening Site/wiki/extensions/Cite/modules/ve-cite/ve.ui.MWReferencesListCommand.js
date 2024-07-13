'use strict';

/*!
 * VisualEditor UserInterface MediaWiki ReferencesListCommand class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * References list command.
 *
 * If a references list node is selected, opens the dialog to edit it.
 * Otherwise inserts the references list for the default group.
 *
 * @constructor
 * @extends ve.ui.Command
 */
ve.ui.MWReferencesListCommand = function VeUiMWReferencesListCommand() {
	// Parent constructor
	ve.ui.MWReferencesListCommand.super.call(
		this, 'referencesList', null, null,
		{ supportedSelections: [ 'linear' ] }
	);
};

/* Inheritance */

OO.inheritClass( ve.ui.MWReferencesListCommand, ve.ui.Command );

/* Methods */

/**
 * @override
 */
ve.ui.MWReferencesListCommand.prototype.execute = function ( surface ) {
	const fragment = surface.getModel().getFragment();
	const selectedNode = fragment.getSelectedNode();
	const isReflistNodeSelected = selectedNode &&
		selectedNode instanceof ve.dm.MWReferencesListNode;

	if ( isReflistNodeSelected ) {
		return surface.execute( 'window', 'open', 'referencesList' );
	} else {
		fragment.collapseToEnd().insertContent( [
			{
				type: 'mwReferencesList',
				attributes: {
					listGroup: 'mwReference/',
					refGroup: '',
					isResponsive: mw.config.get( 'wgCiteResponsiveReferences' )
				}
			},
			{ type: '/mwReferencesList' }
		] );
		return true;
	}
};

/* Registration */

ve.ui.commandRegistry.register( new ve.ui.MWReferencesListCommand() );
