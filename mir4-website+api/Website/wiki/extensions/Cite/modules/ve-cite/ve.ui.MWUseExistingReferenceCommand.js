'use strict';

/*!
 * VisualEditor UserInterface MediaWiki UseExistingReferenceCommand class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * Use existing reference command.
 *
 * @constructor
 * @extends ve.ui.Command
 */
ve.ui.MWUseExistingReferenceCommand = function VeUiMWUseExistingReferenceCommand() {
	// Parent constructor
	ve.ui.MWUseExistingReferenceCommand.super.call(
		this, 'reference/existing', 'window', 'open',
		{ args: [ 'reference', { useExisting: true } ], supportedSelections: [ 'linear' ] }
	);
};

/* Inheritance */

OO.inheritClass( ve.ui.MWUseExistingReferenceCommand, ve.ui.Command );

/* Methods */

/**
 * @override
 */
ve.ui.MWUseExistingReferenceCommand.prototype.isExecutable = function ( fragment ) {
	if ( !ve.ui.MWUseExistingReferenceCommand.super.prototype
		.isExecutable.apply( this, arguments )
	) {
		return false;
	}

	const groups = fragment.getDocument().getInternalList().getNodeGroups();
	for ( const groupName in groups ) {
		if ( groupName.indexOf( 'mwReference/' ) === 0 && groups[ groupName ].indexOrder.length ) {
			return true;
		}
	}
	return false;
};

/* Registration */

ve.ui.commandRegistry.register( new ve.ui.MWUseExistingReferenceCommand() );
