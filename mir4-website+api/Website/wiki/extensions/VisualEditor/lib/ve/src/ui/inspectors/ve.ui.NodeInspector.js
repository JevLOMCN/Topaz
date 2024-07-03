/*!
 * VisualEditor user interface NodeInspector class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Inspector for working with a node.
 *
 * @class
 * @extends ve.ui.FragmentInspector
 * @mixins ve.ui.NodeWindow
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.NodeInspector = function VeUiNodeInspector() {
	// Parent constructor
	ve.ui.NodeInspector.super.apply( this, arguments );

	// Mixin constructor
	ve.ui.NodeWindow.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ui.NodeInspector, ve.ui.FragmentInspector );

OO.mixinClass( ve.ui.NodeInspector, ve.ui.NodeWindow );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.NodeInspector.prototype.initialize = function ( data ) {
	// Parent method
	ve.ui.NodeInspector.super.prototype.initialize.call( this, data );

	// Initialization
	this.$content.addClass( 've-ui-nodeInspector' );
};

/**
 * @inheritdoc
 */
ve.ui.NodeInspector.prototype.getSetupProcess = function ( data ) {
	// Parent method
	var process = ve.ui.NodeInspector.super.prototype.getSetupProcess.call( this, data );
	// Mixin method
	return ve.ui.NodeWindow.prototype.getSetupProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.NodeInspector.prototype.getTeardownProcess = function ( data ) {
	// Parent method
	var process = ve.ui.NodeInspector.super.prototype.getTeardownProcess.call( this, data );
	// Mixin method
	return ve.ui.NodeWindow.prototype.getTeardownProcess.call( this, data, process );
};
