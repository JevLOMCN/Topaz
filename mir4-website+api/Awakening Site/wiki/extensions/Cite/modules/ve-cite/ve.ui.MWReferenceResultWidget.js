'use strict';

/*!
 * VisualEditor UserInterface MWReferenceResultWidget class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * Creates an ve.ui.MWReferenceResultWidget object.
 *
 * @constructor
 * @extends OO.ui.OptionWidget
 * @param {Object} [config] Configuration options
 */
ve.ui.MWReferenceResultWidget = function VeUiMWReferenceResultWidget() {
	// Parent constructor
	ve.ui.MWReferenceResultWidget.super.apply( this, arguments );

	// Initialization
	this.$element
		.addClass( 've-ui-mwReferenceResultWidget' )
		.append(
			$( '<div>' ).addClass( 've-ui-mwReferenceResultWidget-shield' )
		);
};

/* Inheritance */

OO.inheritClass( ve.ui.MWReferenceResultWidget, OO.ui.OptionWidget );
