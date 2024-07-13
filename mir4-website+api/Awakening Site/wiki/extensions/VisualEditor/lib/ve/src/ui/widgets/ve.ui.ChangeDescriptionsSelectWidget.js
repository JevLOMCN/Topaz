/*!
 * VisualEditor UserInterface ChangeDescriptionsSelectWidget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Creates a ve.ui.ChangeDescriptionsSelectWidget object.
 *
 * @class
 * @extends OO.ui.SelectWidget
 *
 * @constructor
 * @param {Object} config Configuration options
 */
ve.ui.ChangeDescriptionsSelectWidget = function VeUiChangeDescriptionsSelectWidget( config ) {
	// Parent constructor
	ve.ui.ChangeDescriptionsSelectWidget.super.call( this, config );

	// DOM
	this.$element.addClass( 've-ui-changeDescriptionsSelectWidget' );
};

/* Inheritance */

OO.inheritClass( ve.ui.ChangeDescriptionsSelectWidget, OO.ui.SelectWidget );

/* Methods */

ve.ui.ChangeDescriptionsSelectWidget.prototype.selectItem = function () {};

ve.ui.ChangeDescriptionsSelectWidget.prototype.pressItem = function () {};
