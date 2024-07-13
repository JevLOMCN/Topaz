'use strict';

/*!
 * VisualEditor MWReferencesListContextItem class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * Context item for a MWReferencesList.
 *
 * @constructor
 * @extends ve.ui.LinearContextItem
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWReferencesListContextItem = function VeUiMWReferencesListContextItem() {
	// Parent constructor
	ve.ui.MWReferencesListContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwReferencesListContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWReferencesListContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MWReferencesListContextItem.static.name = 'referencesList';

ve.ui.MWReferencesListContextItem.static.icon = 'references';

ve.ui.MWReferencesListContextItem.static.label =
	OO.ui.deferMsg( 'cite-ve-dialogbutton-referenceslist-tooltip' );

ve.ui.MWReferencesListContextItem.static.modelClasses = [ ve.dm.MWReferencesListNode ];

ve.ui.MWReferencesListContextItem.static.commandName = 'referencesList';

ve.ui.MWReferencesListContextItem.static.embeddable = false;

/* Methods */

/**
 * @override
 */
ve.ui.MWReferencesListContextItem.prototype.renderBody = function () {
	this.$body.append(
		$( '<div>' ).text( this.getDescription() )
	);
	if ( this.model.getAttribute( 'templateGenerated' ) ) {
		this.$body.append(
			$( '<div>' )
				.addClass( 've-ui-mwReferenceContextItem-muted' )
				.text( ve.msg( 'cite-ve-referenceslist-missingreflist' ) )
		);
	}
};

/**
 * @override
 */
ve.ui.MWReferencesListContextItem.prototype.getDescription = function () {
	const group = this.model.getAttribute( 'refGroup' );

	return group ?
		ve.msg( 'cite-ve-dialog-referenceslist-contextitem-description-named', group ) :
		ve.msg( 'cite-ve-dialog-referenceslist-contextitem-description-general' );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWReferencesListContextItem );
