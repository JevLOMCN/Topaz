'use strict';

/*!
 * VisualEditor UserInterface MWReferenceGroupInput class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * Creates an ve.ui.MWReferenceGroupInput object.
 *
 * @constructor
 * @extends OO.ui.ComboBoxInputWidget
 * @param {Object} config Configuration options
 * @cfg {string} emptyGroupName Label of the placeholder item
 */
ve.ui.MWReferenceGroupInputWidget = function VeUiMWReferenceGroupInputWidget( config ) {
	this.emptyGroupName = config.emptyGroupName;

	// Parent constructor
	ve.ui.MWReferenceGroupInputWidget.super.call(
		this,
		ve.extendObject( { placeholder: config.emptyGroupName }, config )
	);

	this.$element.addClass( 've-ui-mwReferenceGroupInputWidget' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWReferenceGroupInputWidget, OO.ui.ComboBoxInputWidget );

/* Methods */

/**
 * Populate the reference group menu
 *
 * @param {ve.dm.InternalList} internalList Internal list with which to populate the menu
 */
ve.ui.MWReferenceGroupInputWidget.prototype.populateMenu = function ( internalList ) {
	const items = [ new OO.ui.MenuOptionWidget( {
		data: '',
		label: this.emptyGroupName
	} ) ];
	for ( const groupName in internalList.getNodeGroups() ) {
		const match = groupName.match( /^mwReference\/(.+)/ );
		if ( match ) {
			items.push( new OO.ui.MenuOptionWidget( { data: match[ 1 ], label: match[ 1 ] } ) );
		}
	}
	this.menu.clearItems().addItems( items ).toggle( false );
};
