/*!
 * VisualEditor UserInterface Overlay class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Container for content that should appear in front of everything else.
 *
 * @class
 * @abstract
 * @extends OO.ui.Element
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.Overlay = function VeUiOverlay() {
	// Parent constructor
	ve.ui.Overlay.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-overlay' );
};

/* Inheritance */

OO.inheritClass( ve.ui.Overlay, OO.ui.Element );
