/*!
 * VisualEditor UserInterface DimensionsWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Widget that visually displays width and height inputs.
 * This widget is for presentation-only, no calculation is done.
 *
 * @class
 * @extends OO.ui.Widget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {Object} [defaults] Default dimensions
 * @cfg {Object} [validate] Validation pattern passed to TextInputWidgets
 * @cfg {boolean} [readOnly=false] Prevent changes to the value of the widget.
 */
ve.ui.DimensionsWidget = function VeUiDimensionsWidget( config ) {
	// Configuration
	config = config || {};

	// Parent constructor
	ve.ui.DimensionsWidget.super.call( this, config );

	this.widthInput = new OO.ui.TextInputWidget( {
		validate: config.validate || $.isNumeric
	} );
	this.widthInput.$input.attr( 'aria-label', ve.msg( 'visualeditor-dimensionswidget-width' ) );
	this.heightInput = new OO.ui.TextInputWidget( {
		validate: config.validate || $.isNumeric
	} );
	this.heightInput.$input.attr( 'aria-label', ve.msg( 'visualeditor-dimensionswidget-height' ) );

	this.defaults = config.defaults || { width: '', height: '' };
	this.setReadOnly( !!config.readOnly );
	this.renderDefaults();

	var labelTimes = new OO.ui.LabelWidget( {
		label: ve.msg( 'visualeditor-dimensionswidget-times' )
	} );
	var labelPx = new OO.ui.LabelWidget( {
		label: ve.msg( 'visualeditor-dimensionswidget-px' )
	} );

	// Events
	this.widthInput.connect( this, { change: 'onWidthChange' } );
	this.heightInput.connect( this, { change: 'onHeightChange' } );

	// Setup
	this.$element
		.addClass( 've-ui-dimensionsWidget' )
		.append(
			this.widthInput.$element,
			labelTimes.$element
				.addClass( 've-ui-dimensionsWidget-label-times' ),
			this.heightInput.$element,
			labelPx.$element
				.addClass( 've-ui-dimensionsWidget-label-px' )
		);
};

/* Inheritance */

OO.inheritClass( ve.ui.DimensionsWidget, OO.ui.Widget );

/* Events */

/**
 * @event widthChange
 * @param {string} value The new width
 */

/**
 * @event heightChange
 * @param {string} value The new width
 */

/* Methods */

/**
 * Respond to width change, propagate the input change event
 *
 * @param {string} value The new changed value
 * @fires widthChange
 */
ve.ui.DimensionsWidget.prototype.onWidthChange = function ( value ) {
	this.emit( 'widthChange', value );
};

/**
 * Respond to height change, propagate the input change event
 *
 * @param {string} value The new changed value
 * @fires heightChange
 */
ve.ui.DimensionsWidget.prototype.onHeightChange = function ( value ) {
	this.emit( 'heightChange', value );
};

/**
 * Set default dimensions
 *
 * @param {Object} dimensions Default dimensions, width and height
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.setDefaults = function ( dimensions ) {
	if ( dimensions.width && dimensions.height ) {
		this.defaults = ve.copy( dimensions );
		this.renderDefaults();
	}
	return this;
};

/**
 * Render the default dimensions as input placeholders
 */
ve.ui.DimensionsWidget.prototype.renderDefaults = function () {
	this.widthInput.$input.prop( 'placeholder', this.getDefaults().width );
	this.heightInput.$input.prop( 'placeholder', this.getDefaults().height );
};

/**
 * Get the default dimensions
 *
 * @return {Object} Default dimensions
 */
ve.ui.DimensionsWidget.prototype.getDefaults = function () {
	return this.defaults;
};

/**
 * Remove the default dimensions
 *
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.removeDefaults = function () {
	this.defaults = { width: '', height: '' };
	this.renderDefaults();
	return this;
};

/**
 * Check whether the widget is empty.
 *
 * @return {boolean} Both values are empty
 */
ve.ui.DimensionsWidget.prototype.isEmpty = function () {
	return (
		this.widthInput.getValue() === '' &&
		this.heightInput.getValue() === ''
	);
};

/**
 * Set an empty value for the dimensions inputs so they show
 * the placeholders if those exist.
 *
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.clear = function () {
	this.widthInput.setValue( '' );
	this.heightInput.setValue( '' );
	return this;
};

/**
 * Reset the dimensions to the default dimensions.
 *
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.reset = function () {
	this.setDimensions( this.getDefaults() );
	return this;
};

/**
 * Set the dimensions value of the inputs
 *
 * @param {Object} dimensions The width and height values of the inputs
 * @param {number} dimensions.width The value of the width input
 * @param {number} dimensions.height The value of the height input
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.setDimensions = function ( dimensions ) {
	if ( dimensions.width ) {
		this.setWidth( dimensions.width );
	}
	if ( dimensions.height ) {
		this.setHeight( dimensions.height );
	}
	return this;
};

/**
 * Return the current dimension values in the widget
 *
 * @return {Object} dimensions The width and height values of the inputs
 * @return {number} dimensions.width The value of the width input
 * @return {number} dimensions.height The value of the height input
 */
ve.ui.DimensionsWidget.prototype.getDimensions = function () {
	return {
		width: +this.widthInput.getValue(),
		height: +this.heightInput.getValue()
	};
};

/**
 * Disable or enable the inputs
 *
 * @param {boolean} disabled Set disabled or enabled
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.setDisabled = function ( disabled ) {
	// Parent method
	ve.ui.DimensionsWidget.super.prototype.setDisabled.call( this, disabled );

	// The 'setDisabled' method runs in the constructor before the
	// inputs are initialized
	if ( this.widthInput ) {
		this.widthInput.setDisabled( disabled );
	}
	if ( this.heightInput ) {
		this.heightInput.setDisabled( disabled );
	}
	return this;
};

/**
 * Check if the widget is read-only
 *
 * @return {boolean}
 */
ve.ui.DimensionsWidget.prototype.isReadOnly = function () {
	return this.readOnly;
};

/**
 * Set the read-only state of the widget
 *
 * @param {boolean} readOnly Make widget read-only
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.setReadOnly = function ( readOnly ) {
	this.readOnly = readOnly;
	this.widthInput.setReadOnly( readOnly );
	this.heightInput.setReadOnly( readOnly );
	return this;
};

/**
 * Get the current value in the width input
 *
 * @return {string} Input value
 */
ve.ui.DimensionsWidget.prototype.getWidth = function () {
	return this.widthInput.getValue();
};

/**
 * Get the current value in the height input
 *
 * @return {string} Input value
 */
ve.ui.DimensionsWidget.prototype.getHeight = function () {
	return this.heightInput.getValue();
};

/**
 * Set a value for the width input
 *
 * @param {string} value
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.setWidth = function ( value ) {
	this.widthInput.setValue( value );
	return this;
};

/**
 * Set a value for the height input
 *
 * @param {string} value
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.setHeight = function ( value ) {
	this.heightInput.setValue( value );
	return this;
};

/**
 * Sets the 'invalid' flag appropriately on both text inputs.
 *
 * @return {ve.ui.DimensionsWidget}
 * @chainable
 */
ve.ui.DimensionsWidget.prototype.setValidityFlag = function () {
	this.widthInput.setValidityFlag();
	this.heightInput.setValidityFlag();
	return this;
};
