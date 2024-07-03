/*!
 * VisualEditor UserInterface DesktopInspectorWindowManager class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Window manager for desktop inspectors.
 *
 * @class
 * @extends ve.ui.SurfaceWindowManager
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface this belongs to
 * @param {Object} [config] Configuration options
 * @cfg {ve.ui.Overlay} [overlay] Overlay to use for menus
 */
ve.ui.DesktopInspectorWindowManager = function VeUiDesktopInspectorWindowManager( surface, config ) {
	// Parent constructor
	ve.ui.DesktopInspectorWindowManager.super.call( this, surface, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.DesktopInspectorWindowManager, ve.ui.SurfaceWindowManager );

/* Static Properties */

ve.ui.DesktopInspectorWindowManager.static.sizes = {
	small: {
		width: 200,
		maxHeight: '100%'
	},
	medium: {
		width: 300,
		maxHeight: '100%'
	},
	large: {
		width: 400,
		maxHeight: '100%'
	},
	full: {
		// These can be non-numeric because they are never used in calculations
		width: '100%',
		height: '100%'
	}
};
