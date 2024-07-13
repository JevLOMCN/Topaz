/*!
 * VisualEditor Standalone Initialization Desktop Target class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Initialization standalone desktop target.
 *
 * @class
 * @extends ve.init.sa.Target
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {Object} [toolbarConfig] Configuration options for the toolbar
 */
ve.init.sa.DesktopTarget = function VeInitSaDesktopTarget( config ) {
	// Parent constructor
	ve.init.sa.DesktopTarget.super.call( this, config );

	this.$element.addClass( 've-init-sa-desktopTarget' );
};

/* Inheritance */

OO.inheritClass( ve.init.sa.DesktopTarget, ve.init.sa.Target );
