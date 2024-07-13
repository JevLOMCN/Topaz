/*!
 * VisualEditor UserInterface WhitespacePreservingTextInputWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Text input widget which hides but preserves leading and trailing whitespace
 *
 * @class
 * @extends OO.ui.MultilineTextInputWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {string} [valueAndWhitespace=''] Initial value and whitespace
 * @cfg {number} [limit] Maximum number of characters to preserve at each end
 */
ve.ui.WhitespacePreservingTextInputWidget = function VeUiWhitespacePreservingTextInputWidget( config ) {
	// Configuration
	config = config || {};

	// Parent constructor
	ve.ui.WhitespacePreservingTextInputWidget.super.call( this, config );

	this.limit = config.limit;

	this.setWhitespace( [ '', '' ] );
	this.setValueAndWhitespace( config.valueAndWhitespace || '' );

	this.$element.addClass( 've-ui-whitespacePreservingTextInputWidget' );
};

/* Inheritance */

OO.inheritClass( ve.ui.WhitespacePreservingTextInputWidget, OO.ui.MultilineTextInputWidget );

/* Methods */

/**
 * Set the value of the widget and extract whitespace.
 *
 * @param {string} value
 */
ve.ui.WhitespacePreservingTextInputWidget.prototype.setValueAndWhitespace = function ( value ) {
	var leftValue = this.limit ? value.slice( 0, this.limit ) : value;
	this.whitespace[ 0 ] = leftValue.match( /^\s*/ )[ 0 ];
	value = value.slice( this.whitespace[ 0 ].length );

	var rightValue = this.limit ? value.slice( -this.limit ) : value;
	this.whitespace[ 1 ] = rightValue.match( /\s*$/ )[ 0 ];
	value = value.slice( 0, value.length - this.whitespace[ 1 ].length );

	this.setValue( value );
};

/**
 * Set the value of the widget and extract whitespace.
 *
 * @param {string[]} whitespace Outer whitespace
 */
ve.ui.WhitespacePreservingTextInputWidget.prototype.setWhitespace = function ( whitespace ) {
	this.whitespace = whitespace;
};

/**
 * Get the value of text widget, including hidden outer whitespace
 *
 * @return {string} Text widget value including whitespace
 */
ve.ui.WhitespacePreservingTextInputWidget.prototype.getValueAndWhitespace = function () {
	if ( !this.whitespace ) {
		// In case getValue() is called from a parent constructor
		return this.value;
	}
	return this.whitespace[ 0 ] + this.value + this.whitespace[ 1 ];
};
