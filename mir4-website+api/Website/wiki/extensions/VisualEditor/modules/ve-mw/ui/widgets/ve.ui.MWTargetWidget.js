/*!
 * VisualEditor UserInterface MWTargetWidget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Creates an ve.ui.MWTargetWidget object.
 *
 * @class
 * @abstract
 * @extends ve.ui.TargetWidget
 *
 * @constructor
 * @param {Object} config
 * @cfg {string[]} [surfaceClasses] Surface classes to apply
 */
ve.ui.MWTargetWidget = function VeUiMWTargetWidget( config ) {
	this.surfaceClasses = ve.copy( config.surfaceClasses ) || [];

	// Parent constructor
	ve.ui.MWTargetWidget.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwTargetWidget' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTargetWidget, ve.ui.TargetWidget );

/**
 * @inheritdoc
 */
ve.ui.MWTargetWidget.prototype.createTarget = function () {
	return new ve.init.mw.Target( {
		register: false,
		toolbarGroups: this.toolbarGroups,
		modes: this.modes,
		defaultMode: this.defaultMode,
		surfaceClasses: this.surfaceClasses
	} );
};
