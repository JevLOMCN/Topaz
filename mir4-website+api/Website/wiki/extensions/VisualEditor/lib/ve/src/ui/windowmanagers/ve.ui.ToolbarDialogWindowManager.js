/*!
 * VisualEditor UserInterface ToolbarDialogWindowManager class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Window manager for toolbar dialogs.
 *
 * @class
 * @extends ve.ui.SurfaceWindowManager
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface this belongs to
 * @param {Object} [config] Configuration options
 * @cfg {ve.ui.Overlay} [overlay] Overlay to use for menus
 */
ve.ui.ToolbarDialogWindowManager = function VeUiToolbarDialogWindowManager( surface, config ) {
	// Parent constructor
	ve.ui.ToolbarDialogWindowManager.super.call( this, surface, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.ToolbarDialogWindowManager, ve.ui.SurfaceWindowManager );

/* Static Properties */

ve.ui.ToolbarDialogWindowManager.static.sizes = {
	full: {
		width: '100%',
		maxHeight: '100%'
	},
	small: {
		width: 150
	},
	medium: {
		width: 300
	},
	large: {
		width: 400
	}
};

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.ToolbarDialogWindowManager.prototype.getTeardownDelay = function () {
	return 250;
};

/**
 * Get an object describing the amount of padding the toolbar dialog adds to the surface.
 *
 * @return {null|Object} Padding object, or null
 */
ve.ui.ToolbarDialogWindowManager.prototype.getSurfacePadding = function () {
	var currentWindow = this.getCurrentWindow();
	if ( currentWindow && currentWindow.constructor.static.position === 'below' ) {
		return { bottom: currentWindow.$frame[ 0 ].clientHeight };
	} else {
		return { bottom: 0 };
	}
};
