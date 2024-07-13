/*!
 * VisualEditor UserInterface MWPreTextInputWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Text input widget which hides but preserves a single leading and trailing newline.
 *
 * @class
 * @extends ve.ui.WhitespacePreservingTextInputWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWPreTextInputWidget = function VeUiMWPreTextInputWidget( config ) {
	// Parent constructor
	ve.ui.MWPreTextInputWidget.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWPreTextInputWidget, ve.ui.WhitespacePreservingTextInputWidget );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWPreTextInputWidget.prototype.setValueAndWhitespace = function ( value ) {
	this.whitespace[ 0 ] = value.match( /^\n?/ )[ 0 ];
	value = value.slice( this.whitespace[ 0 ].length );

	this.whitespace[ 1 ] = value.match( /\n?$/ )[ 0 ];
	value = value.slice( 0, value.length - this.whitespace[ 1 ].length );

	this.setValue( value );
};
