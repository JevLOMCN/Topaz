/*!
 * VisualEditor UserInterface MWParameterCheckboxInputWidget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Checkbox input for a parameter value which can only contain boolean values.
 *
 * @class
 * @extends OO.ui.CheckboxInputWidget
 *
 * @constructor
 */
ve.ui.MWParameterCheckboxInputWidget = function VeUiMWParameterCheckboxInputWidget() {
	// Parent constructor
	ve.ui.MWParameterCheckboxInputWidget.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWParameterCheckboxInputWidget, OO.ui.CheckboxInputWidget );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWParameterCheckboxInputWidget.prototype.getValue = function () {
	return this.isSelected() ? '1' : '0';
};

/**
 * @inheritdoc
 */
ve.ui.MWParameterCheckboxInputWidget.prototype.setValue = function ( value ) {
	return this.setSelected( value === '1' );
};
