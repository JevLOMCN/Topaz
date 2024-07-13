/*!
 * VisualEditor UserInterface MobileWindowManager class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Window manager for mobile windows.
 *
 * @class
 * @extends ve.ui.SurfaceWindowManager
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface this belongs to
 * @param {Object} [config] Configuration options
 * @cfg {ve.ui.Overlay} [overlay] Overlay to use for menus
 */
ve.ui.MobileWindowManager = function VeUiMobileWindowManager( surface, config ) {
	// Parent constructor
	ve.ui.MobileWindowManager.super.call( this, surface, config );

	// Initialization
	this.$element.addClass( 've-ui-mobileWindowManager' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MobileWindowManager, ve.ui.SurfaceWindowManager );

/* Static Properties */

// Only allow 'small' and 'full' sizes, defaulting to 'full'
ve.ui.MobileWindowManager.static.sizes = {
	small: ve.ui.MobileWindowManager.super.static.sizes.small,
	full: ve.ui.MobileWindowManager.super.static.sizes.full
};

ve.ui.MobileWindowManager.static.defaultSize = 'full';
