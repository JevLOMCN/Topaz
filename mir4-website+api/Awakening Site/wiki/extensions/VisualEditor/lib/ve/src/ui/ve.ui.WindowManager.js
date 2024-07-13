/*!
 * VisualEditor UserInterface WindowManager class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Window manager.
 *
 * @class
 * @extends OO.ui.WindowManager
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {ve.ui.Overlay} [overlay] Overlay to use for menus
 */
ve.ui.WindowManager = function VeUiWindowManager( config ) {
	// Configuration initialization
	config = config || {};

	// Parent constructor
	ve.ui.WindowManager.super.call( this, config );

	// Properties
	this.overlay = config.overlay || null;

	// The following classes are used here:
	// * ve-ui-dir-block-ltr
	// * ve-ui-dir-block-rtl
	this.$element.addClass( 've-ui-dir-block-' + this.getDir() );
};

/* Inheritance */

OO.inheritClass( ve.ui.WindowManager, OO.ui.WindowManager );

/* Methods */

/**
 * Get directionality.
 *
 * @return {string} UI directionality
 */
ve.ui.WindowManager.prototype.getDir = function () {
	return $( document.body ).css( 'direction' );
};

/**
 * Get overlay for menus.
 *
 * @return {ve.ui.Overlay|null} Menu overlay, null if none was configured
 */
ve.ui.WindowManager.prototype.getOverlay = function () {
	return this.overlay;
};
